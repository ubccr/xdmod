<?php

   use CCR\DB;
   
   class LDIF {
   
      private $_pdo;                // Database handle
      
      private $_enum_query;         // Query used to provide an enumeration of users
      private $_identifier;         // Text involved in the LDIF file (content)
      private $_identifier_fname;   // Text involved in the LDIF filename
      
      private $_context_filter;     // String used to filter the set against username / first name / last name
      private $_ldif_content;       // Data which comprises the LDIF
      
      // ----------------------------------------------------------------------      
      
      function __construct($group_filter = 'all', $role_filter = 'any', $context_filter = '') {
      
         $this->_pdo = DB::factory('database');
      
         try {
         
            $txt_group_filter = $this->_validateGroupFilter($group_filter);
            $txt_role_filter = $this->_validateRoleFilter($role_filter);
            
            $this->_context_filter = $context_filter;
            
            $this->_enum_query = \xd_dashboard\deriveUserEnumerationQuery($group_filter, $role_filter, $context_filter, true);

            $this->_identifier = $txt_group_filter.', '.$txt_role_filter;            
            $this->_identifier_fname = $this->_sanitizeToFilename($this->_identifier);            
                                  
         }
         catch(Exception $e) {
         
            print $e->getMessage();
         
         }
            
      }//__construct

      // ----------------------------------------------------------------------
            
      private function _sanitizeToFilename($original_text) {
      
         $sanitized_text = str_replace(' ', '_', $original_text);
         $sanitized_text = preg_replace('/[^A-Za-z_]/', '', $sanitized_text);
         
         return strtolower($sanitized_text); 
                  
      }//_sanitizeToFilename

      // ----------------------------------------------------------------------      

      private function _validateRoleFilter($filter) {

         if ($filter == 'any') return 'All Roles';
         
         if (!is_numeric($filter)) {
         
            throw new Exception('Invalid role filter specified');

         }
         
         $r = $this->_pdo->query("SELECT description FROM moddb.Roles WHERE role_id=:role_id", array('role_id' => $filter));
         
         if (!isset($r[0])) {
         
            throw new Exception('Invalid role filter specified');
         
         }
         
         return $r[0]['description'].' Roles Only';

      }//_validateRoleFilter
      
      // ----------------------------------------------------------------------
            
      private function _validateGroupFilter($filter) {
      
         if ($filter == 'all') {
         
            return 'All Groups';
         
         }
         else if ($filter == XSEDE_USER_TYPE) {
            
            return 'XSEDE Group';
         
         }
         else {
            
            $r = $this->_pdo->query("SELECT type FROM moddb.UserTypes WHERE id=:id", array('id' => $filter));
            
            if (!isset($r[0])) {
            
               throw new Exception('Invalid group filter specified');

            }
            
            return $r[0]['type'].' Group';
       
         }     

      }//_validateGroupFilter
      
      // ----------------------------------------------------------------------
      
      public function generate() {
   
         if (!isset($this->_enum_query)) {
         
            print "cannot generate LDIF -- construction failed";
            return;
            
         }
                  
         $this->_ldif_content = "";
        
         $r = $this->_pdo->query($this->_enum_query, array(':filter' => $this->_context_filter));
         
         $acquired_addresses = array();
         $member_dn = array();
         $member_block = array();

         foreach ($r as $e) {
        
            $formal_name = $e['formal_name'];
            $email_address = $e['email_address'];
               
            if (!in_array($email_address, $acquired_addresses)) {
      
               $acquired_addresses[] = $email_address;
      
               $member_dn[] = "member: cn=$formal_name,mail=$email_address\n";
      
               $b  = "dn: cn=$formal_name,mail=$email_address\n";
               $b .= "objectclass: top\n";
               $b .= "objectclass: person\n";
               $b .= "objectclass: organizationalPerson\n";
               $b .= "objectclass: inetOrgPerson\n";
               $b .= "objectclass: mozillaAbPersonAlpha\n";
               $b .= "cn: $formal_name\n";
               $b .= "mail: $email_address\n";
               $b .= "modifytimestamp: 0\n\n";
      
               $member_block[] = $b;
      
            }

         }//foreach

         foreach($member_block as $mb) {
            $this->_ldif_content .= $mb;
         }

         $datestamp = date('Y-m-d');

         $this->_ldif_content .= "dn: cn=XDMoD Users ({$this->_identifier}) $datestamp\n";
         $this->_ldif_content .= "objectclass: top\n";
         $this->_ldif_content .= "objectclass: groupOfNames\n";
         $this->_ldif_content .= "cn: XDMoD Users ({$this->_identifier}) $datestamp\n";

         foreach($member_dn as $mdn) {
            $this->_ldif_content .= $mdn;
         }

         $this->_ldif_content .= "\n";
         
      }//generate

      // ==================================================================
      
      public function dump() {
      
         if (!isset($this->_ldif_content)) {
         
            print "cannot dump LDIF -- it must be generated first";
            return;
            
         }
               
         print '<pre>'.$this->_ldif_content.'</pre>';
         return;
               
      }//dump

      // ==================================================================      

      public function present() {

         if (!isset($this->_ldif_content)) {
         
            print "cannot present LDIF -- it must be generated first";
            return;
            
         }
         
         header("Pragma: public");
         header("Expires: 0"); // set expiration time
         header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
         // browser must download file from server instead of cache
         
         // force download dialog
         header("Content-Type: application/force-download");
         header("Content-Type: application/octet-stream");
         header("Content-Type: application/download");
         
         // use the Content-Disposition header to supply a recommended filename and
         // force the browser to display the save dialog.
         
         $datestamp = date('Y-m-d');
          
         $filtered = (!empty($this->_context_filter)) ? 'filtered_' : '';
         
         $filename = "xdmod_accounts_{$this->_identifier_fname}_$filtered$datestamp.ldif";
         
         header("Content-Disposition: attachment; filename=$filename;");
         
         /*
         The Content-transfer-encoding header should be binary, since the file will be read
         directly from the disk and the raw bytes passed to the downloading computer.
         The Content-length header is useful to set for downloads. The browser will be able to
         show a progress meter as a file downloads. The content-lenght can be determines by
         filesize function returns the size of a file.
         */
         
         header("Content-Transfer-Encoding: binary");
         header("Content-Length: ".strlen($this->_ldif_content));
         
         print $this->_ldif_content;
      
      }//present
      
   }//class LDIF
   
?>
