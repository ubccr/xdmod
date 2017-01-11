<?php

class Glossary
{
   
   // Processes text against the glossary, tagging all the qualifying terms.
   // Returns an HTML version of the text, which is used along with the javascript code (below)
   // for generating tooltips presenting definitions for the term that is currently being hovered over.
      
   /*
      
   NOTE: Whichever page is intended to handle the output of this call, the following JavaScript functions 
      *must* be defined on that page (either inline or included).
      
   <script language="JavaScript">
      
   function presentTerm(e, term, definition) {
      ...
   }//presentTerm
         
   function hideTerm() {
     ...
   }//hideTerm
         
   </script>  
      
   */
      
      
    public static function processText($string)
    {
         
        // Pad the string left and right with a space so the regular expression (see $pattern) can match appropriately.
        $string = ' '.$string.' ';
            
        $res = DataWarehouse::connect()->query('SELECT term, definition FROM moddb.Glossary');
         
        $active_terms = array();
         
        foreach ($res as $term) {
            $t = $term['term'];
            $base_term = ucwords($t);
            $definition = mysql_escape_string($term['definition']);
            
            $term_collection = array($t);
            
            // ----------------------------------
            
            // Grab all the aliases for the term (should any exist), so they can be tagged as well.
            
            $aliasResults = DataWarehouse::connect()->query("SELECT alias FROM moddb.GlossaryAliases WHERE term = '$t'");
               
            foreach ($aliasResults as $alias) {
                $term_collection[] = $alias['alias'];
            }
                           
            // ----------------------------------
            
            foreach ($term_collection as $term) {
               // The pattern accounts for 'common' pluralization (using 's') and possession (apostrophe s)
               // Todo: Still have to account for a closing parenthesis immediately to the right of the term (e.g. "application kernels)" )
               
                $pattern = "/\s(($term)('{0,1}s)?)\s/i";
               
                $string = preg_replace($pattern, " <span class=\"dict\" onmouseover=\"presentTerm(this, '$base_term', '$definition')\" onmouseout=\"hideTerm()\">\\1</span> ", $string);
            }//foreach
        }//foreach
      
        return $string;
    }//processText
}//Glossary
