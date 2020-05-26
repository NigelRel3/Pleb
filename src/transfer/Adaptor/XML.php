<?php
namespace Pleb\transfer\Adaptor;

use Pleb\transfer\Sink;
use Pleb\transfer\Source;
use Pleb\transfer\Entity;
use XMLReaderReg\XMLReaderReg;

class XML extends Entity  {
    use Source;
    use Sink;
    
    private $fileName = null;
    private $fileHandle = null;
    
    protected $filter = null;
    
    public function setFileName( string $fileName )   {
        $this->fileName = $fileName;
    }
    
    protected function configure()  {}
    
    public function filter( $filter )   {
        if ( is_string($filter) )    {
            $this->filter = $filter;
        }
        else    {
            // If not a string, see if it can be handled by the normal route
            try {
                parent::filter($filter);
            }
            catch ( \InvalidArgumentException $e )   {
                throw new \InvalidArgumentException("JSON::filter must be passed a string or a callable");
            }
        }
        return $this;
    }
    
    /**
     * From https://stackoverflow.com/a/6167346/1213708
     * 
     * @param \SimpleXMLElement $xmlObject
     * @param array $out
     * @return array
     */
    protected function xml2array ( \SimpleXMLElement $xmlObject, $out = array () )
    {
        foreach ( (array) $xmlObject as $index => $node )   {
            $out[$index] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;
        }
        return $out;
    }
    
    public function open ( string $mode )   {
        if ( $this->fileHandle === null )   {
            if ( $mode == "r" && !file_exists($this->name) )    {
                throw new \RuntimeException("File {$this->name} not found.");
            }
        }
        
        if ( $mode == "r" ) {
            if ( $this->filter === null )    {
                throw new \RuntimeException("Filter needed for an XML source.");
            }
            
            // Use push to process XML data
            $reader = new XMLReaderReg();
            $reader->open($this->name);
            $reader->process([$this->filter =>function ( \SimpleXMLElement $data, $path ) {
                $data = $this->xml2array($data);
                $this->push( $data );
            }]);
            $reader->close();
        }
        else    {
            // Set template mask
            if ( !empty($this->fields) )    {
                $this->templateRecord = array_keys($this->fields);
            }
        }
    }
    
    public function close()  : void    {
        if ( $this->fileHandle !== null )   {
            fwrite($this->fileHandle, json_encode($this->data, 
                $this->outputFlags ));
            fclose($this->fileHandle);
        }
        $this->fileHandle = null;
    }
    
    public function getShutdown()   {
        return function () { $this->close(); };
    }
    
    protected $outputFlags = JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | 
            JSON_UNESCAPED_SLASHES;
    
    public function outputFormat ( int $flags = JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK
                | JSON_UNESCAPED_SLASHES) {
        $this->outputFlags = $flags;
        return $this;
    }
    
    /**
     * For JSON files, the data is 'push'ed from the open method.
     * @return boolean
     */
    protected function read() {
        return false;
    }
    
    public function write ( array $data )   {
        if ( $this->exportFormat != null )  {
            $data = ($this->exportFormat)($data );
        }
        if ( !empty($this->templateRecord) )    {
            $data = $this->processFilter($data, $this->templateRecord);
        }
        $this->data[] = $data;
    }
    
    public function indexBy ( $fields )   {
        $indexedData = new Transient();
        $indexedData->indexBy($fields);
        
        $from = clone $this;
        $from->saveTo($indexedData)
            ->transfer();
        return $indexedData;
    }
}
