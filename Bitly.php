<?php

namespace modelo\utilitarios;

class Bitly {

	private $cookies = [];

	public function __construct(){
		$this->generateCookies();
	}

	private function generateCookies(){

		$cr = curl_init();
		curl_setopt($cr, CURLOPT_URL, "https://bitly.com/");
		curl_setopt($cr, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($cr, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($cr, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($cr, CURLOPT_HEADER, TRUE);
		$return = curl_exec($cr);

		$header_size 	= curl_getinfo($cr, CURLINFO_HEADER_SIZE);
		$headers 	 	= explode(PHP_EOL, substr($return, 0, $header_size));

		curl_close($cr);

		$cookies = [];
		foreach ($headers as $header) {
			if(strpos($header, "Set-Cookie") !== FALSE){

				$header = str_replace("Set-Cookie: ", "", $header);

				$delimiter 	= strpos($header, ";");
				$cookie 	= substr($header, 0, $delimiter);

				$delimiter 	= strpos($cookie, "=");
				$key 		= substr($cookie, 0, $delimiter);
				$value 		= substr($cookie, ($delimiter+1));
				
				$cookies[$key] = $value;
			}
		}

		if(!isset($cookies['_xsrf']) or strlen($cookies['_xsrf']) != 32){
			throw new \Exception("xsrf token invalid or not found", 500);
		}

		if(!isset($cookies['anon_u'])){
			throw new \Exception("anon_u token not found", 500);
		}

		$this->cookies = $cookies;
	}

	public function shorten($longUrl){

		$cr = curl_init();
		curl_setopt($cr, CURLOPT_URL, "https://bitly.com/data/shorten");
		curl_setopt($cr, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($cr, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($cr, CURLOPT_RETURNTRANSFER, TRUE); 
		curl_setopt($cr, CURLOPT_POST, TRUE); 
		curl_setopt($cr, CURLOPT_POSTFIELDS, "url={$longUrl}");
		curl_setopt($cr, CURLOPT_HTTPHEADER, [
			"Cookie: _xsrf={$this->cookies['_xsrf']}; anon_u=?",
			"x-xsrftoken: {$this->cookies['_xsrf']}"
		]);

		$retorno = curl_exec($cr); 
		curl_close($cr);

		$object = json_decode($retorno);

		if(!is_object($object)){
			throw new \Exception("Return is not a valid json", 500);
		}

		if($object->status_code !== 200 || $object->status_txt !== "OK"){
			throw new \Exception($object->status_txt, $object->status_code);
		}

		return $object->data->anon_shorten->link;
	}
}

?>