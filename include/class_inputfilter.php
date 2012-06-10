<?php

/* $Id: class_inputfilter.php 87 2009-03-10 01:03:44Z john $ */


/** @class: InputFilter (PHP4 & PHP5, with comments)
  * @project: PHP Input Filter
  * @date: 10-05-2005
  * @version: 1.2.2_php4/php5
  * @author: Daniel Morris
  * @contributors: Gianpaolo Racca, Ghislain Picard, Marco Wandschneider, Chris Tobin and Andrew Eddie.
  * @copyright: Daniel Morris
  * @email: dan@rootcube.com
  * @license: GNU General Public License (GPL)
  */
  
// CHECK IF CLASS EXISTS FIRST
if(class_exists('InputFilter')) return;

class InputFilter
{
  var $decode_flags = 1; // 1 - special chars, 2 - entities, 4 - manual decimal entities, 8 - manual hex entities
  var $charset = 'UTF-8'; // ISO-8859-1, UTF-8
  
  var $tagsArray;			// default = empty array
  var $attrArray;			// default = empty array

  var $tagsMethod;		// default = 0
  var $attrMethod;		// default = 0

  var $xssAuto;           // default = 1
//var $tagBlacklist = array('applet', 'body', 'bgsound', 'base', 'basefont', 'embed', 'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer', 'layer', 'link', 'meta', 'name', 'object', 'script', 'style', 'title', 'xml');
  var $tagBlacklist = array('applet', 'body', 'bgsound', 'base', 'basefont', 'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer', 'layer', 'link', 'meta', 'name', 'script', 'style', 'title', 'xml');
  var $attrBlacklist = array('action', 'background', 'codebase', 'dynsrc', 'lowsrc');  // also will strip ALL event handlers
    
  /** 
    * Constructor for inputFilter class. Only first parameter is required.
    * @access constructor
    * @param Array $tagsArray - list of user-defined tags
    * @param Array $attrArray - list of user-defined attributes
    * @param int $tagsMethod - 0= allow just user-defined, 1= allow all but user-defined
    * @param int $attrMethod - 0= allow just user-defined, 1= allow all but user-defined
    * @param int $xssAuto - 0= only auto clean essentials, 1= allow clean blacklisted tags/attr
    */
  function InputFilter($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1) {		
    // make sure user defined arrays are in lowercase
    for ($i = 0; $i < count($tagsArray); $i++) $tagsArray[$i] = strtolower($tagsArray[$i]);
    for ($i = 0; $i < count($attrArray); $i++) $attrArray[$i] = strtolower($attrArray[$i]);
    // assign to member vars
    $this->tagsArray = (array) $tagsArray;
    $this->attrArray = (array) $attrArray;
    $this->tagsMethod = $tagsMethod;
    $this->attrMethod = $attrMethod;
    $this->xssAuto = $xssAuto;
    
    // Check charset for PHP4
    if( version_compare(phpversion(), '5.0', '<') && strtoupper($this->charset)=="UTF-8" )
      $this->charset = NULL;
  }
  
  /** 
    * Method to be called by another php script. Processes for XSS and specified bad code.
    * @access public
    * @param Mixed $source - input string/array-of-string to be 'cleaned'
    * @return String $source - 'cleaned' version of input parameter
    */
  function process($source) {
    // clean all elements in this array
    if (is_array($source)) {
      foreach($source as $key => $value)
        // filter element for XSS and other 'bad' code etc.
        if (is_string($value)) $source[$key] = $this->remove($this->decode($value));
      return $source;
    // clean this string
    } else if (is_string($source)) {
      // filter source for XSS and other 'bad' code etc.
      return $this->remove($this->decode($source));
    // return parameter as given
    } else return $source;	
  }

  /** 
    * Internal method to iteratively remove all unwanted tags and attributes
    * @access protected
    * @param String $source - input string to be 'cleaned'
    * @return String $source - 'cleaned' version of input parameter
    */
  function remove($source) {
    $loopCounter=0;
    $source = $this->quickValidate($source);
    // provides nested-tag protection
    while($source != $this->filterTags($source)) {
      $source = $this->filterTags($source);
      $loopCounter++;
    }
    return $source;
  }	
  
  /** 
    * Internal method to strip a string of certain tags
    * @access protected
    * @param String $source - input string to be 'cleaned'
    * @return String $source - 'cleaned' version of input parameter
    */
  function filterTags($source) {
    // filter pass setup
    $preTag = NULL;
    $postTag = $source;
    // find initial tag's position
    $tagOpen_start = strpos($source, '<');
    // interate through string until no tags left
    while($tagOpen_start !== FALSE) {
      // process tag interatively
      $preTag .= substr($postTag, 0, $tagOpen_start);
      $postTag = substr($postTag, $tagOpen_start);
      $fromTagOpen = substr($postTag, 1);
      // end of tag
      $tagOpen_end = strpos($fromTagOpen, '>');
      if ($tagOpen_end === false) break;
      // next start of tag (for nested tag assessment)
      $tagOpen_nested = strpos($fromTagOpen, '<');
      if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end)) {
        $preTag .= substr($postTag, 0, ($tagOpen_nested+1));
        $postTag = substr($postTag, ($tagOpen_nested+1));
        $tagOpen_start = strpos($postTag, '<');
        continue;
      } 
      $tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
      $currentTag = substr($fromTagOpen, 0, $tagOpen_end);
      $tagLength = strlen($currentTag);
      if (!$tagOpen_end) {
        $preTag .= $postTag;
        $tagOpen_start = strpos($postTag, '<');			
      }
      // iterate through tag finding attribute pairs - setup
      $tagLeft = $currentTag;
      $attrSet = array();
      $currentSpace = strpos($tagLeft, ' ');

      // is end tag
      if (substr($currentTag, 0, 1) == "/") {
        $isCloseTag = TRUE;
        list($tagName) = explode(' ', $currentTag);
        $tagName = substr($tagName, 1);
      // is start tag
      } else {
        $isCloseTag = FALSE;
        list($tagName) = explode(' ', $currentTag);
      }		
      // excludes all "non-regular" tagnames OR no tagname OR remove if xssauto is on and tag is blacklisted
      if ((!preg_match("/^[a-z][a-z0-9]*$/i",$tagName)) || (!$tagName) || ((in_array(strtolower($tagName), $this->tagBlacklist)) && ($this->xssAuto))) { 				
        $postTag = substr($postTag, ($tagLength + 2));
        $tagOpen_start = strpos($postTag, '<');
        // don't append this tag
        continue;
      }

      // this while is needed to support attribute values with spaces in!
      while ($currentSpace !== FALSE) {
        $fromSpace = substr($tagLeft, ($currentSpace+1));
        $nextSpace = strpos($fromSpace, ' ');
        $openQuotes = strpos($fromSpace, '"');
        $closeQuotes = strpos(substr($fromSpace, ($openQuotes+1)), '"') + $openQuotes + 1;
        // another equals exists
        if (strpos($fromSpace, '=') !== FALSE) {
          // opening and closing quotes exists
          if (($openQuotes !== FALSE) && (strpos(substr($fromSpace, ($openQuotes+1)), '"') !== FALSE))
            $attr = substr($fromSpace, 0, ($closeQuotes+1));
          // one or neither exist
          else $attr = substr($fromSpace, 0, $nextSpace);
        // no more equals exist
        } else $attr = substr($fromSpace, 0, $nextSpace);

        // last attr pair
        if (!$attr) $attr = $fromSpace;
        // add to attribute pairs array
        $attrSet[] = $attr;
        // next inc
        $tagLeft = substr($fromSpace, strlen($attr));
        $currentSpace = strpos($tagLeft, ' ');
      }

      // appears in array specified by user
      $tagFound = in_array(strtolower($tagName), $this->tagsArray);			
      // remove this tag on condition
      if ((!$tagFound && $this->tagsMethod) || ($tagFound && !$this->tagsMethod)) {
        // reconstruct tag with allowed attributes
        if (!$isCloseTag) {
          $attrSet = $this->filterAttr($attrSet);
          $preTag .= '<' . $tagName;
          for ($i = 0; $i < count($attrSet); $i++)
            $preTag .= ' ' . $attrSet[$i];
          // reformat single tags to XHTML
          if (strpos($fromTagOpen, "</" . $tagName)) $preTag .= '>';
          else $preTag .= ' />';
        // just the tagname
        } else $preTag .= '</' . $tagName . '>';
      }
      // find next tag's start
      $postTag = substr($postTag, ($tagLength + 2));
      $tagOpen_start = strpos($postTag, '<');			
    }
    // append any code after end of tags
    $preTag .= $postTag;
    return $preTag;
  }

  /** 
    * Internal method to strip a tag of certain attributes
    * @access protected
    * @param Array $attrSet
    * @return Array $newSet
    */
  function filterAttr($attrSet) {	
    $newSet = array();
    // process attributes
    for ($i = 0; $i <count($attrSet); $i++) {
      // skip blank spaces in tag
      if (!$attrSet[$i]) continue;
      // split into attr name and value
      //$attrSubSet = explode('=', trim($attrSet[$i]));
      $attrSubSet = explode('=', trim($attrSet[$i]), 2);
      list($attrSubSet[0]) = explode(' ', $attrSubSet[0]);
      // removes all "non-regular" attr names AND also attr blacklisted
      if ((!eregi("^[a-z]*$",$attrSubSet[0])) || (($this->xssAuto) && ((in_array(strtolower($attrSubSet[0]), $this->attrBlacklist)) || (substr($attrSubSet[0], 0, 2) == 'on')))) 
        continue;
      // xss attr value filtering
      if ($attrSubSet[1]) {
        // strips unicode, hex, etc
        $attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);
        // strip normal newline within attr value
      //  $attrSubSet[1] = preg_replace('/\s+/', '', $attrSubSet[1]);
        $attrSubSet[1] = preg_replace('/[\r\n]+/', '', $attrSubSet[1]);
        // strip double quotes
        $attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);
        // [requested feature] convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr value)
        if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'"))
          $attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
        // strip slashes
        $attrSubSet[1] = stripslashes($attrSubSet[1]);
      }
      // auto strip attr's with "javascript:
      if (	((strpos(strtolower($attrSubSet[1]), 'expression') !== false) &&	(strtolower($attrSubSet[0]) == 'style')) ||
          (strpos(strtolower($attrSubSet[1]), 'javascript:') !== false) ||
          (strpos(strtolower($attrSubSet[1]), 'behaviour:') !== false) ||
          (strpos(strtolower($attrSubSet[1]), 'vbscript:') !== false) ||
          (strpos(strtolower($attrSubSet[1]), 'mocha:') !== false) ||
          (strpos(strtolower($attrSubSet[1]), 'livescript:') !== false) 
      ) continue;

      // if matches user defined array
      $attrFound = in_array(strtolower($attrSubSet[0]), $this->attrArray);
      // keep this attr on condition
      if ((!$attrFound && $this->attrMethod) || ($attrFound && !$this->attrMethod)) {
        // attr has value
        if ($attrSubSet[1]) $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
        // attr has decimal zero as value
        else if ($attrSubSet[1] == "0") $newSet[] = $attrSubSet[0] . '="0"';
        // reformat single attributes to XHTML
        else $newSet[] = $attrSubSet[0] . '="' . $attrSubSet[0] . '"';
      }	
    }
    return $newSet;
  }
  
  /** 
    * Try to convert to plaintext
    * @access protected
    * @param String $source
    * @return String $source
    */
  function decode($source)
  {
    // convert special chars
    if( $this->decode_flags & 1 )
      $source = $this->htmlspecialchars_decode($source, ENT_QUOTES);
    
    // convert entities
    if( $this->decode_flags & 2 )
      $source = html_entity_decode($source, ENT_QUOTES, $this->charset);
    
    // convert decimal entities
    if( $this->decode_flags & 4 )
      $source = preg_replace('/&#(\d+);/me',"chr(\\1)", $source);
    
    // convert hex entities
    if( $this->decode_flags & 8 )
      $source = preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)", $source);
    
    return $source;
  }
  
  function htmlspecialchars_decode($text, $ent_quotes = ENT_COMPAT)
  {
    if( function_exists('htmlspecialchars_decode') )
      return htmlspecialchars_decode($text, $ent_quotes);
    
    if( $ent_quotes === ENT_QUOTES   ) $text = str_replace("&quot;", "\"", $text);
    if( $ent_quotes !== ENT_NOQUOTES ) $text = str_replace("&#039;", "'", $text);
    $text = str_replace("&lt;", "<", $text);
    $text = str_replace("&gt;", ">", $text);
    $text = str_replace("&amp;", "&", $text);
    return $text;
  }

  /** 
    * Method to be called by another php script. Processes for SQL injection
    * @access public
    * @param Mixed $source - input string/array-of-string to be 'cleaned'
    * @param Buffer $connection - An open MySQL connection
    * @return String $source - 'cleaned' version of input parameter
    */
  function safeSQL($source, &$connection) {
    // clean all elements in this array
    if (is_array($source)) {
      foreach($source as $key => $value)
        // filter element for SQL injection
        if (is_string($value)) $source[$key] = $this->quoteSmart($this->decode($value), $connection);
      return $source;
    // clean this string
    } else if (is_string($source)) {
      // filter source for SQL injection
      if (is_string($source)) return $this->quoteSmart($this->decode($source), $connection);
    // return parameter as given
    } else return $source;	
  }

  /** 
    * @author Chris Tobin
    * @author Daniel Morris
    * @access protected
    * @param String $source
    * @param Resource $connection - An open MySQL connection
    * @return String $source
    */
  function quoteSmart($source, &$connection) {
    // strip slashes
    if (get_magic_quotes_gpc()) $source = stripslashes($source);
    // quote both numeric and text
    $source = $this->escapeString($source, $connection);
    return $source;
  }
  
  /** 
    * @author Chris Tobin
    * @author Daniel Morris
    * @access protected
    * @param String $source
    * @param Resource $connection - An open MySQL connection
    * @return String $source
    */	
  function escapeString($string, &$connection) {
    // depreciated function
    if (version_compare(phpversion(),"4.3.0", "<")) mysql_escape_string($string);
    // current function
    else mysql_real_escape_string($string);
    return $string;
  }
  
  /** 
    * @author John Boehr
    * @access protected
    * @param String $source
    * @return String $source
    */	
  function quickValidate($string)
  {
    // Tries to validate proper closing and opening tags for HTML elements
    // NOTE: This would cause problems with <img src="image.gif" alt="I<3you" />
    // however prevents <iframe src="xss.html" <
    return preg_replace('/<([a-z]{1}[^<>]*)[<>]?/i', '<\1>', $string);
  }
}

?>