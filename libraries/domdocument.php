<?php

	namespace xd_domdocument;

	// --------------------------------

	function createElement(&$dom, &$node, $elementText, $text) {
					
		$elementNode = $dom->createElement($elementText);
		$node->appendChild($elementNode);
			
		$textNode = $dom->createTextNode($text);
		$elementNode->appendChild($textNode);
		
	}//createElement

?>
