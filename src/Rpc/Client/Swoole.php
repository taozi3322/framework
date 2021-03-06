<?php
/**
 *  Swoole RPC client
 *  
 * @author          Kaihui Wang <hpuwang@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @since           16/5/31
 */
namespace Kerisy\Rpc\Client;

use Kerisy\Core\Exception;

class Swoole extends Base{
 
    private $host=null;
    private $port=null;
    private $client = null;
    private $fnData = null;
    private $recvfn = null;
    private  $sourceType = null;
    private $compressType = null;
    
    static $timeout = 2;
    
    function __construct($host,$port)
    {
        $this->host = $host;
        $this->port =$port;
    }

    
    function invoke($sourceType,$fn,$compressType=0){
        $client = new \swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_SYNC);
        $data = $this->syncSendAndReceive($client,$fn,$sourceType,$compressType);
        return $data;

    }
    
    
    function invokeAsy($sourceType,$fn,$recvfn,$compressType=0){
        $client = new \swoole_client(SWOOLE_SOCK_TCP,SWOOLE_SOCK_ASYNC);
        $this->client = $client;
        
        $this->fnData = $fn;
        $this->recvfn = $recvfn;
        $this->sourceType = $sourceType;
        $this->compressType = $compressType;
 
        $this->client->on('connect', [$this, 'onClientConnect']);
        $this->client->on('receive', [$this, 'onClientReceive']);
        $this->client->on('error', [$this, 'onClientError']);
        $this->client->on('close', [$this, 'onClientClose']);
        if (!$this->client->connect($this->host, $this->port, self::$timeout)) {
            throw new \Exception(socket_strerror($this->client->errCode));
        }
    }

    function syncSendAndReceive($client,$fnData,$sourceType,$compressType=0){
        if(!$client->isConnected()){
            if (!$client->connect($this->host, $this->port, self::$timeout)) {
                throw new \Exception("connect failed");
            }
        }
        if($this->send($client,$fnData,$sourceType,$compressType)){
            try {
                $data = $client->recv();
            }catch (Exception $e){
                throw new \Exception(socket_strerror($client->errCode));
            }
            if ($data === false) {
                throw new \Exception(socket_strerror($client->errCode));
            }
            $data = $this->getResult($data);
            return $data;
        }else {
            throw new \Exception(socket_strerror($client->errCode));
        }
        $data = $this->getResult("");
        return $data;
    }
    
    public function onClientConnect($client){
        $this->send($client,$this->fnData,$this->sourceType,$this->compressType);
    }

    public function onClientReceive($client,$dataBuffer){
        if ($dataBuffer === "") {
            throw new \Exception("connection closed");
        }
        else{
            if ($dataBuffer === false) {
                throw new \Exception(socket_strerror($client->errCode));
            }else{
                $data = $this->getResult($dataBuffer);
            }
        }
        if(gettype($this->recvfn) == "object"){
            return call_user_func_array($this->recvfn,array($data));
        }
    }

    public function onClientError($client){
        throw new \Exception(socket_strerror($client->errCode));
    }

    public function onClientClose($client){

    }

}