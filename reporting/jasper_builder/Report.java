/*

Author: Lizhe Wang
Date: Dec 1 2010
   
Last Updated: May 4, 2012

*/

import java.util.ArrayList;

//!ELEMENT Report ( User, Title, pageHeader, Section*, pageFooter ) >
public class Report {

	private String UserLastName;
	private String UserFirstName;
	private String Email;
	private String Title;
	private String pageHeader;
	private String Format;
	private ArrayList<MySection> sections;
	private String pageFooter;

   // ---------------------------------------------
	
	public String getUserLastName() {
		return UserLastName;
	}
   
   // ---------------------------------------------
   
	public String getUserFirstName() {
		return UserFirstName;
	}
	
   // ---------------------------------------------
	   
	public void setUserLastName(String userLastName) {
		UserLastName = userLastName;
	}

   // ---------------------------------------------
   
	public void setUserFirstName(String userFirstName) {
		UserFirstName = userFirstName;
	}

   // ---------------------------------------------
   
	public String getEmail() {
		return Email;
	}

   // ---------------------------------------------
   
	public void setEmail(String email) {
		Email = email;
	}

   // ---------------------------------------------
   
	public String getTitle() {
		return Title;
	}

   // ---------------------------------------------
   
	public void setTitle(String title) {
		Title = title;
	}
	
   // ---------------------------------------------
   
	public String getPageHeader() {
		return pageHeader;
	}

   // ---------------------------------------------
   
	public void setPageHeader(String pageHeader) {
		this.pageHeader = pageHeader;
	}

   // ---------------------------------------------
   
	public ArrayList<MySection> getSections() {
		return sections;
	}

   // ---------------------------------------------
   
	public void setSections(ArrayList<MySection> sections) {
		this.sections = sections;
	}
	
   // ---------------------------------------------
   
	public String getPageFooter() {
		return pageFooter;
	}
	
   // ---------------------------------------------
   
	public void setPageFooter(String pageFooter) {
		this.pageFooter = pageFooter;
	}
	
   // ---------------------------------------------
   
	public String getFormat() {
		return Format;
	}
	
   // ---------------------------------------------
   
	public void setFormat(String format) {
		Format = format;
	}
	
}//Report
