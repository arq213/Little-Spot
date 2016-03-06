<?php
//_______________________________________________________________________
//Este código implementa el plugin nube de tags que es utilizada en Little Spot CMS
//************************************************************************

function obtenerArrayTagsNumOccursYUrlsFromBD($pdo)
{
	$array_keywordsDeTodosArts = array();
	try
	{
		//1. Leemos todos las columnas tags de la tabla tag_num_occurs:
		$rows_pdoStmt = $pdo->query("SELECT tag, url_tag, numOccurs FROM tag_num_occurs;"); //obtenemos un objeto PDOSTATEMENT
		$array_tags = $rows_pdoStmt->fetchAll(); //ejecutamos método fech de nuestro PDOSTATEMENT object, que nos da un array indexado por "clave"(asociativo) y tb por índice-pos
	} catch (PDOException $e)
	{
		echo 'Connection failed: ', $e->getMessage();
		//$pdo->rollback();//DEVOLVEMOS BD al estado de datos estable-seguro anterior
	}
	if( !empty($array_tags) ) //pasamos el otro array (de forma array[0]['clave1'],array[0]['clave2']) a otro de forma: array['clave'] => valor (más simple de entender y tratar)
	{
		$numfilas = count($array_tags);
		for( $i=0;$i < $numfilas; $i++ )
		{
			$nombreTag = $array_tags[$i]['tag'];
			$tag_url = $array_tags[$i]['url_tag'];
			$numOccursTag = $array_tags[$i]['numOccurs'];
			$array_keywordsDeTodosArts[$tag_url][0] = $numOccursTag;
			$array_keywordsDeTodosArts[$tag_url][1] = $nombreTag;
			//echo "<h1 style=\"color:black;\">Tag: ",$nombreTag,"</h1>";
		}
	}
	return $array_keywordsDeTodosArts;
}

function imprimeNubeTagsAPartirArrayTagsYNumOccurs($array_keywordsUrlsDeTodosArts)
{
	$cloud = new tagcloud();
	
	foreach ($array_keywordsUrlsDeTodosArts as $tag_url_elem => $arrayTagYNumOccursPorUrlTag) {
					$cloud->addTag(array('tag' => $arrayTagYNumOccursPorUrlTag[1], 'url' => $tag_url_elem, 'size' => $arrayTagYNumOccursPorUrlTag[0]));unset($array_keywordsUrlsDeTodosArts[$tag_url_elem]);}
		/* set the minimum length required */
		$cloud->setMinLength(3);
		/* limiting the output */
		$cloud->setLimit(40);
		echo $cloud->render();
}

function cargarEImprimirNubeTags($pdo)
{
	//1. cargamos los datos de tags y su nº de ocurrencias desde la BD a un array de tipo asoc (array['clave']=>valor): 
	$array_keywordsDeTodosArts = obtenerArrayTagsNumOccursYUrlsFromBD( $pdo );
	//2, mostramos la nube de tags:
	imprimeNubeTagsAPartirArrayTagsYNumOccurs($array_keywordsDeTodosArts);
}

//https://github.com/lotsofcode/tag-cloud:
class tagcloud
{
	/* Tag array container*/
	protected $_tagsArray = array();
	protected $_removeTags = array();
	protected $_attributes = array();
	protected $_limit = null;
	protected $_minLength = null;
	protected $_formatting = array('transformation' => 'lower','trim' => true);
	protected $_htmlizeTagFunction = null;
	public function __construct($tags = false)
	{
		if ($tags !== false) {
			if (count($tags)) {
				foreach ($tags as $key => $value) {
					$this->addTag($value);
				}
			}
		}
	}

	 /* Parse tag into safe format      @param string $string         @return string*/
	public function formatTag($string)
	{
		if ($this->_formatting['transformation']) {
			switch ($this->_formatting['transformation']) {
				case 'upper':
					$string = strtoupper($string);
					break;
				default:
					$string = strtolower($string);
			}
		}
		if ($this->_formatting['trim']) {
			$string = trim($string);
		}
		//Si tengo que filtrar -->return preg_replace("/[^\w'áéíóúñÑüÜçÇÁÉÍÓÚ\.,:\?\¿\¡\!\[\]}{\(\)\+\*\$#€&%@\|<>ºª\\/ -]/u", '', strip_tags($string));// /u,utf8 - /i,case insensitive->los dos sería /ui
		return preg_replace("/[^\s\S]/u", '', strip_tags($string));// /u,utf8 - /i,case insensitive->los dos sería /ui  [/s/S]->A character set that can be used to match any character, including line breaks.
	}  //hago el preg_replace sencillo porque creo innecesario tanto filtro (paso a "" ningun caracter (NOT  de todos los caracteres[^/s/S]))

	 /* Assign tag to array       @param array $tagAttributes Tags or tag attributes array          @return array $this->tagsArray*/
	public function addTag($tagAttributes = array())
	{
		$tagAttributes['tag'] = $this->formatTag($tagAttributes['tag']);
		if (!array_key_exists('size', $tagAttributes)) {
			$tagAttributes = array_merge($tagAttributes, array('size' => 1));
		}
		if (!array_key_exists('tag', $tagAttributes)) {
			return false;
		}
		$tag = $tagAttributes['tag'];
		if (empty($this->_tagsArray[$tag])) {
			$this->_tagsArray[$tag] = array();
		}
		if (!empty($this->_tagsArray[$tag]['size']) && !empty($tagAttributes['size'])) {
			$tagAttributes['size'] = ($this->_tagsArray[$tag]['size'] + $tagAttributes['size']);
		} elseif (!empty($this->_tagsArray[$tag]['size'])) {
			$tagAttributes['size'] = $this->_tagsArray[$tag]['size'];
		}
		$this->_tagsArray[$tag] = $tagAttributes;
		$this->addAttributes($tagAttributes);
		return $this->_tagsArray[$tag];
	}

	 /* Add all attributes to cached array .  @return void*/
	public function addAttributes($attributes)
	{
		$this->_attributes = array_unique(
			array_merge(
				$this->_attributes,
				array_keys($attributes)
			)
		);
	}

	/* Sets a minimum string length for the tags to display.       @param int $minLength          @returns obj $this*/
	public function setMinLength($minLength)
	{
		$this->_minLength = $minLength;
		return $this;
	}

	 /* Gets the minimum length value  @returns void*/
	public function getMinLength()
	{
		return $this->_minLength;
	}

	/* Sets a limit for the amount of clouds     @param int $limi     @returns obj $this*/
	public function setLimit($limit){$this->_limit = $limit;return $this;}

	/* Get the limit for the amount tags to display      @param int $limit      @returns int $this->_limit*/
	public function getLimit(){return $this->_limit;}
	public function setRemoveTag($tag){$this->_removeTags[] = $this->formatTag($tag);return $this;}
	public function setRemoveTags($tags){foreach ($tags as $tag) {$this->setRemoveTag($tag);}return $this;}
	public function getRemoveTags(){return $this->_removeTags;}
	/* Assign the order field and order direction of the array
	 *Order by tag or size / defaults to random     @param array  $field   @param string $sortway   @returns $this->orderBy */
	public function setOrder($field, $direction = 'ASC'){return $this->orderBy = array('field' => $field,'direction' => $direction);}
	public function setHtmlizeTagFunction($htmlizer){return $this->_htmlizeTagFunction = $htmlizer;}

	/*Generate the output for each tag.       @returns string/array $return*/
	public function render($returnType = 'html')
	{
		$this->_remove();
		$this->_minLength();
		if (empty($this->orderBy)) {
			$this->_shuffle();
		} else {
			$orderDirection = strtolower($this->orderBy['direction']) == 'desc' ? 'SORT_DESC' : 'SORT_ASC';
			$this->_tagsArray = $this->_order(
				$this->_tagsArray,
				$this->orderBy['field'],
				$orderDirection
			);
		}
		$this->_limit();
		$max = $this->_getMax();
		if (is_array($this->_tagsArray)) {
			$return = ($returnType == 'html' ? '' : ($returnType == 'array' ? array() : ''));
			foreach ($this->_tagsArray as $tag => $arrayInfo) {
				$sizeRange = $this->_getClassFromPercent(($arrayInfo['size'] / $max) * 100);
				$arrayInfo['range'] = $sizeRange;
				if ($returnType == 'array') {
					$return [$tag] = $arrayInfo;
				} elseif ($returnType == 'html') {
					$return .= $this->htmlizeTag( $arrayInfo, $sizeRange );
				}
			}
			return $return;
		}
		return false;
	}

	public function htmlizeTag($arrayInfo, $sizeRange)
	{
		if ( isset($this->_htmlizeTagFunction) ) {
			// this cannot be writen in one line or the PHP interpreter will puke
			// appearantly, it's okay to have a function in a variable,
			// but it's not okay to have it in an instance-varriable.
			$htmlizer = $this->_htmlizeTagFunction;
			return $htmlizer($arrayInfo, $sizeRange);
		} else {
			return "<a class='linktagNubeTags' href=\"tag/{$arrayInfo['url']}/\"><span class='tag size{$sizeRange}'> &nbsp; {$arrayInfo['tag']} &nbsp; </span></a>";
		}
	}

	protected function _remove(){foreach ($this->_tagsArray as $key => $value) {if (!in_array($value['tag'], $this->getRemoveTags())) {$_tagsArray[$value['tag']] = $value;}}
		$this->_tagsArray = array();$this->_tagsArray = $_tagsArray;return $this->_tagsArray;}
	/* Orders the cloud by a specific field    @param array $unsortedArray    @param string $sortField   @param string $sortWay  @returns array $unsortedArray*/
	protected function _order($unsortedArray, $sortField, $sortWay = 'SORT_ASC'){$sortedArray = array();foreach ($unsortedArray as $uniqid => $row) {
			foreach ($this->getAttributes() as $attr) {if (isset($row[$attr])) {$sortedArray[$attr][$uniqid] = $unsortedArray[$uniqid][$attr];
				} else {$sortedArray[$attr][$uniqid] = null;}}}
		if ($sortWay) {array_multisort($sortedArray[$sortField], constant($sortWay), $unsortedArray);}return $unsortedArray;}

	/* Parses the array and retuns limited amount of items      @returns array $this->_tagsArray*/
	protected function _limit(){$limit = $this->getLimit();if ($limit !== null) {$i = 0;$_tagsArray = array();
		foreach ($this->_tagsArray as $key => $value) {if ($i < $limit) {$_tagsArray[$value['tag']] = $value;}$i++;}
		$this->_tagsArray = array();$this->_tagsArray = $_tagsArray;} return $this->_tagsArray;}

	/* Reduces the array by removing strings with a length shorter than the minLength  @returns array $this->_tagsArray*/
	protected function _minLength(){$limit = $this->getMinLength();if ($limit !== null) {$i = 0;$_tagsArray = array();
			foreach ($this->_tagsArray as $key => $value) {if (strlen($value['tag']) >= $limit) {$_tagsArray[$value['tag']] = $value;}
				$i++;}
			$this->_tagsArray = array();$this->_tagsArray = $_tagsArray;}return $this->_tagsArray;}

	/*Finds the maximum 'size' value of an array     @returns string $max*/
	protected function _getMax(){$max = 0;if (!empty($this->_tagsArray)) {$p_size = 0;foreach ($this->_tagsArray as $cKey => $cVal) {$c_size = $cVal['size'];
				if ($c_size > $p_size) {$max = $c_size;$p_size = $c_size;}}}return $max;}

	/*Shuffle associated names in array    @return array $this->_tagsArray The shuffled array*/
	protected function _shuffle()
	{
		$keys = array_keys($this->_tagsArray);
		shuffle($keys);
		if (count($keys) && is_array($keys)) {
			$tmpArray = $this->_tagsArray;
			$this->_tagsArray = array();
			foreach ($keys as $key => $value)
				$this->_tagsArray[$value] = $tmpArray[$value];
		}
		return $this->_tagsArray;
	}

	/* Get the class range using a percentage    @returns int $class The respective class name based on the percentage value*/
	protected function _getClassFromPercent($percent)
	{
		$percent = floor($percent);
		if ($percent >= 99)
			$class = 9;
		elseif ($percent >= 70)
			$class = 8;
		elseif ($percent >= 60)
			$class = 7;
		elseif ($percent >= 50)
			$class = 6;
		elseif ($percent >= 40)
			$class = 5;
		elseif ($percent >= 30)
			$class = 4;
		elseif ($percent >= 20)
			$class = 3;
		elseif ($percent >= 10)
			$class = 2;
		elseif ($percent >= 5)
			$class = 1;
		else
			$class = 0;
		return $class;
	}
}
?>
