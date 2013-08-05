<?php

function binary2hex($str) {
$str = str_replace(" ", "", $str);
$text_array = explode("\r\n", chunk_split($str, 8));
for ($n = 0; $n < count($text_array) - 1; $n++) {
$newstring .= base_convert($text_array[$n], 2, 16);
}
$newstring = chunk_split($newstring, 2, " ");
return $newstring;
}


function asc2bin($str) {
$text_array = explode("\r\n", chunk_split($str, 1));
for ($n = 0; $n < count($text_array) - 1; $n++) {
$newstring .= substr("0000".base_convert(ord($text_array[$n]), 10, 2), -8);
}
$newstring = chunk_split($newstring, 8, " ");
return $newstring;
}

function hex2binary($str) {
$str = str_replace(" ", "", $str);
$text_array = explode("\r\n", chunk_split($str, 2));
for ($n = 0; $n < count($text_array) - 1; $n++) {
$newstring .= substr("0000".base_convert($text_array[$n], 16, 2), -8);
}
$newstring = chunk_split($newstring, 8, " ");
return $newstring;
}
 
class ttfInfo {
	/**
	* variable $_dirRestriction
	* Restrict the resource pointer to this directory and above.
	* Change to 1 for to allow the class to look outside of it current directory
	* @protected
	* @var int
	*/
	public	  $log;
	protected $_dirRestriction = 1;
	/**
	* variable $_dirRestriction
	* Restrict the resource pointer to this directory and above.
	* Change to 1 for nested directories
	* @protected
	* @var int
	*/
	protected $_recursive = 1;

	/**
	* variable $fontsdir
	* This is to declare this variable as protected
	* don't edit this!!!
	* @protected
	*/
	protected $fontsdir;
	/**
	* variable $filename
	* This is to declare this varable as protected
	* don't edit this!!!
	* @protected
	*/
	protected $filename;

	/**
	* function setFontFile()
	* set the filename
	* @public
	* @param string $data the new value
	* @return object reference to this
	*/
	public function setFontFile($data)
	{
		if ($this->_dirRestriction && preg_match('[\.\/|\.\.\/]', $data))
		{
			$this->exitClass('Error: Directory restriction is enforced!');
		}

		$this->filename = $data;
		return $this;
	} // public function setFontFile

	/**
	* function setFontsDir()
	* set the Font Directory
	* @public
	* @param string $data the new value
	* @return object referrence to this
	*/
	public function setFontsDir($data)
	{
		if ($this->_dirRestriction && preg_match('[\.\/|\.\.\/]', $data))
		{
			$this->exitClass('Error: Directory restriction is enforced!');
		}

		$this->fontsdir = $data;
		return $this;
	} // public function setFontsDir

	/**
	* function readFontsDir() 
	* @public
	* @return information contained in the TTF 'name' table of all fonts in a directory.
	*/
	public function readFontsDir()
	{
		if (empty($this->fontsdir)) { $this->exitClass('Error: Fonts Directory has not been set with setFontsDir().'); }
		if (empty($this->backupDir)){ $this->backupDir = $this->fontsdir; }

		$this->array = array();
		$d = dir($this->fontsdir);

		while (false !== ($e = $d->read()))
		{
			if($e != '.' && $e != '..')
			{
				$e = $this->fontsdir . $e;
				if($this->_recursive && is_dir($e))
				{
					$this->setFontsDir($e);
					$this->array = array_merge($this->array, readFontsDir());
				}
				else if ($this->is_ttf($e) === true)
				{
					$this->setFontFile($e);
					$this->array[$e] = $this->getFontInfo();
				}
			}
		}

		if (!empty($this->backupDir)){ $this->fontsdir = $this->backupDir; }

		$d->close();
		return $this;
	} // public function readFontsDir

	/**
	* function setProtectedVar()
	* @public
	* @param string $var the new variable
	* @param string $data the new value
	* @return object reference to this

	* DISABLED, NO REAL USE YET

	public function setProtectedVar($var, $data)
	{
		if ($var == 'filename')
		{
			$this->setFontFile($data);
		} else {
			//if (isset($var) && !empty($data))
			$this->$var = $data;
		}
		return $this;
	}
	*/
	/**
	* function getFontInfo() 
	* @public
	* @return information contained in the TTF 'name' table.
	*/
	public function getFontInfo()
	{
		$fd = fopen ($this->filename, "r");
		$this->text = fread ($fd, filesize($this->filename));
		fclose ($fd);

		$number_of_tables = hexdec($this->dec2ord($this->text[4]).$this->dec2ord($this->text[5]));

		for ($i=0;$i<$number_of_tables;$i++)
		{
			$tag = $this->text[12+$i*16].$this->text[12+$i*16+1].$this->text[12+$i*16+2].$this->text[12+$i*16+3];
			
			$log = "123";
			
			if ($tag == 'name')
			{
				$this->ntOffset = hexdec(
					$this->dec2ord($this->text[12+$i*16+8]).$this->dec2ord($this->text[12+$i*16+8+1]).
					$this->dec2ord($this->text[12+$i*16+8+2]).$this->dec2ord($this->text[12+$i*16+8+3]));

				$offset_storage_dec = hexdec($this->dec2ord($this->text[$this->ntOffset+4]).$this->dec2ord($this->text[$this->ntOffset+5]));
				$number_name_records_dec = hexdec($this->dec2ord($this->text[$this->ntOffset+2]).$this->dec2ord($this->text[$this->ntOffset+3]));
			}
			
			if ($tag == 'cmap')
			{
				$this->cmOffset = hexdec(
					$this->dec2ord($this->text[12+$i*16+8]).$this->dec2ord($this->text[12+$i*16+8+1]).
					$this->dec2ord($this->text[12+$i*16+8+2]).$this->dec2ord($this->text[12+$i*16+8+3]));

				$offset_storage_dec = hexdec($this->dec2ord($this->text[$this->ntOffset+4]).$this->dec2ord($this->text[$this->ntOffset+5]));
				$number_name_records_dec = hexdec($this->dec2ord($this->text[$this->ntOffset+2]).$this->dec2ord($this->text[$this->ntOffset+3]));
			}
			
		}

		$storage_dec = $offset_storage_dec + $this->ntOffset;
		$storage_hex = strtoupper(dechex($storage_dec));
		

		//$font_tags['Filename'] = $this->filename;
		//$font_tags['DisplayNames'] = array();
		//$font_tags['num'] = $number_name_records_dec;
		for ($j=0;$j<$number_name_records_dec;$j++)
		{
			$platform_id_dec	= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+0]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+1]));
			$specific_id_dec	= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+2]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+3]));
			$lang_id_dec		= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+4]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+5]));
			$name_id_dec		= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+6]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+7]));
			$string_length_dec	= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+8]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+9]));
			$string_offset_dec	= hexdec($this->dec2ord($this->text[$this->ntOffset+6+$j*12+10]).$this->dec2ord($this->text[$this->ntOffset+6+$j*12+11]));
			
			$key = "$lang_id_dec";
			//$font_tags[$key]['sid'] = $specific_id_dec;
					
			//if( $lang_id_dec != 1028 && $lang_id_dec != 1033 && $lang_id_dec != 3076 )
			//	continue;
			if (!empty($name_id_dec) && empty($font_tags[$key][$name_id_dec]))
			//if (!empty($name_id_dec))
			{
				if( $platform_id_dec != 3 )
					continue;
				//$font_tags[$key][1] = string();
				/*
				if( $lang_id_dec == "1028" && $platform_id_dec == 3 )
				{
					$font_tags['DisplayNames'][]['locale'] = "zh-TW";
				}else if( $lang_id_dec == "1033" && $platform_id_dec == 3 )
				{
					$font_tags['DisplayNames'][]['locale'] = "en-US";
				}else if( $lang_id_dec == "3076" && $platform_id_dec == 3 )
				{
					$font_tags['DisplayNames'][]['locale'] = "zh-HK";
				}
				*/
				for($l=0;$l<$string_length_dec;$l++)
				{
					
					//if (ord($this->text[$storage_dec+$string_offset_dec+$l]) == '0') { continue; }
					//else {
						//if($name_id_dec >= 3) continue;
						$font_tags[$key][$name_id_dec] .= ($this->text[$storage_dec+$string_offset_dec+$l]);
						//$font_tags[$key][$name_id_dec] .= iconv("UCS-2","UTF-8",$this->text[$storage_dec+$string_offset_dec+$l]);
						
						//if($name_id_dec==1)
						//	echo utf8_encode($this->text[$storage_dec+$string_offset_dec+$l]).$this->text[$storage_dec+$string_offset_dec+$l]."<br>";
					//}
					/*
					if ($name_id_dec >= 3) { continue; }
					else  if( $lang_id_dec == 0 )
					{ $font_tags[$name_id_dec] .= ($this->text[$storage_dec+$string_offset_dec+$l]); }
					else
					{
						if( $lang_id_dec == "1028" && $name_id_dec == 1 )
						{
							$font_tags['locale'] = "zh-TW";
							$font_tags[$name_id_dec] .= ($this->text[$storage_dec+$string_offset_dec+$l]);
						}else if( $lang_id_dec == "1033" && $name_id_dec == 1 )
						{
							$font_tags['locale'] = "en-US";
							$font_tags[$name_id_dec] .= ($this->text[$storage_dec+$string_offset_dec+$l]);
						}else if( $lang_id_dec == "3076" && $name_id_dec == 1 )
						{
							$font_tags['locale'] = "zh-HK";
							$font_tags[$name_id_dec] .= ($this->text[$storage_dec+$string_offset_dec+$l]);
						}					
					}
					*/
				}
				//$font_tags[$key][1] = utf8_encode($font_tags[$key][1]);
				//$font_tags[$name_id_dec] .= ($this->text[$storage_dec+$string_offset_dec+$l]);
				//$font_tags['lang'] = $lang_id_dec;
			}
			
		}
		return $font_tags;
	} // public function getFontInfo

	/**
	* function dec2ord()
	* Used to lessen redundant calls to multiple functions.
	* @protected
	* @return object
	*/
	protected function dec2ord($dec)
	{
		return $this->dec2hex(ord($dec));
	} // protected function dec2ord

	/**
	* function dec2hex()
	* private function to perform Hexadecimal to decimal with proper padding.
	* @protected
	* @return object
	*/
	protected function dec2hex($dec)
	{
		return str_repeat('0', 2-strlen(($hex=strtoupper(dechex($dec))))) . $hex;
	} // protected function dec2hex

	/**
	* function dec2hex()
	* private function to perform Hexadecimal to decimal with proper padding.
	* @protected
	* @return object
	*/
	protected function exitClass($message)
	{
		echo $message;
		exit;
	} // protected function dec2hex

	/**
	* function dec2hex()
	* private helper function to test in the file in question is a ttf.
	* @protected
	* @return object
	*/
	protected function is_ttf($file)
	{
		$ext = explode('.', $file);
		$ext = $ext[count($ext)-1];
		return preg_match("/ttf$/i",$ext) ? true : false;
	} // protected function is_ttf
} // class ttfInfo

function getFontInfo($resource)
{
	$ttfInfo = new ttfInfo;
	$ttfInfo->setFontFile($resource);
	return $ttfInfo->getFontInfo();
}
?>