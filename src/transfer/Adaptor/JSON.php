<?php
namespace Pleb\transfer\Adaptor;

use Pleb\transfer\Sink;
use Pleb\transfer\Source;
use Pleb\transfer\Entity;
use JsonStreamingParser\Listener\RegexListener;
use JsonStreamingParser\Parser;

class JSON extends Entity  {
    use Source;
    use Sink;
    
    private $fileName = null;
    private $fileHandle = null;
    
    protected $filter = null;
    
    public function setFileName( string $fileName )   {
        $this->fileName = $fileName;
    }
    
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
    
    public function open ( string $mode ) : void   {
        if ( $this->fileHandle === null )   {
            if ( $mode == "r" && !file_exists($this->name) )    {
                throw new \RuntimeException("File {$this->name} not found.");
            }
            $this->fileHandle = fopen( $this->name, $mode);
            if ( $this->fileHandle === false )    {
                throw new \RuntimeException("Unable to read {$this->name}.");
            }
        }
        
        if ( $mode == "r" ) {
            if ( $this->filter === null )    {
                throw new \RuntimeException("Filter needed for a JSON source.");
            }
            
            // Use push to process JSON data
            $listener = new RegexListener( [$this->filter =>function ( $data, $path ) {
                $this->push($data);
            }]);
                
            $parser = new Parser($this->fileHandle, $listener);
            $parser->parse();
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
