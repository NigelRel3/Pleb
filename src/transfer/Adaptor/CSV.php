<?php
namespace Pleb\transfer\Adaptor;

use Pleb\transfer\Sink;
use Pleb\transfer\Source;
use Pleb\transfer\Entity;

class CSV extends Entity  {
    use Source;
    use Sink;
    
    protected $enclosure = '"';
    protected $delimeter = ",";
    protected $headerLines = 1;
    protected $headerLabels = null;
    protected $headerWritten = false;
    
    protected $fileHandle = null;
        
    protected function configure() {}
    
    public function open ( string $mode ) : void   {
        if ( $this->fileHandle === null )   {
            if ( $mode == "r" && !file_exists($this->name) )    {
                throw new \RuntimeException("File {$this->name} not found.");
            }
            $this->fileHandle = fopen( $this->name, $mode);
            if ( $this->fileHandle === false )    {
                throw new \RuntimeException("Unable to read {$this->name}.");
            }
            if ( $mode == "r" ) {
                for ( $i = 0; $i < $this->headerLines; $i++ )   {
                    $this->headerLabels = fgetcsv($this->fileHandle, null, $this->delimeter, $this->enclosure);
                    $this->headerLabels = array_map("trim",$this->headerLabels);
                }
                if ( !empty($this->fields) )    {
                    $this->headerLabels = array_keys($this->fields);
                }
            }
            else    {
                // Put out column headers for output
                $this->templateRecord = array_keys($this->fields);
                if ( count ($this->templateRecord) > 0 )    {
                    fputcsv($this->fileHandle, $this->templateRecord,
                        $this->delimeter, $this->enclosure);
                    $this->headerWritten = true;
                }
            }
        }
    }
    
    public function close()  : void    {
        if ( $this->fileHandle !== null )   {
            fclose($this->fileHandle);
        }
        $this->fileHandle = null;
    }
    
    public function getShutdown()   {
        return function () { $this->close(); };
    }
    
    protected function read() {
        $data = fgetcsv($this->fileHandle, null, $this->delimeter, $this->enclosure);
        if ( $data !== false )  {
            $data = array_combine($this->headerLabels, $data);
        }
        
        return $data;
    }
    
    public function write ( array $data )   {
        if ( $this->exportFormat != null )  {
        	// If formatting fails then exit
        	if ( ($data = ($this->exportFormat)( $data )) === false )	{
        		return;
        	}
        }
        // Allow if no fields set, that the whole record is written
        if ( $this->templateRecord == null )    {
            if ( $this->headerWritten == false )    {
                fputcsv($this->fileHandle, array_keys($data),
                    $this->delimeter, $this->enclosure);
                $this->headerWritten = true;
            }
        }
        else    {
            $data = $this->processFilter($data, $this->templateRecord);
        }
        fputcsv($this->fileHandle, $data, $this->delimeter, $this->enclosure);
    }
    
    
    public function setDelimeter ( string $delimeter )   {
        $this->delimeter = $delimeter;
        return $this;
    }
    
    public function setEnclosure ( string $enclosure )   {
        $this->enclosure = $enclosure;
        return $this;
    }
    
    public function indexBy ( array $fields )   {
        $indexedData = new Transient();
        $indexedData->indexBy($fields);
        
        $from = clone $this;
        $from->saveTo($indexedData)
            ->transfer();
        return $indexedData;
    }
    
}
