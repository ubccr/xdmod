/*

Author: Lizhe Wang
Date: Dec 1 2010

Last Updated: May 4, 2012

*/

import org.w3c.dom.Document;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import org.w3c.dom.NodeList;
import java.util.ArrayList;

public class XmlParser {

	public static Report parse(String fileName) {

		ArrayList<MySection> al = new ArrayList<MySection>();
		Report userprofile = new Report();
		DocumentBuilderFactory f = DocumentBuilderFactory.newInstance();

		try {

			int count = 0;
			DocumentBuilder builder = f.newDocumentBuilder();
			Document d = builder.parse(fileName);

			String firstname = d.getElementsByTagName("FirstName").item(0).getFirstChild().getNodeValue();
			String lastname = d.getElementsByTagName("LastName").item(0).getFirstChild().getNodeValue();
			String email =  d.getElementsByTagName("Email").item(0).getFirstChild().getNodeValue();
			String title = d.getElementsByTagName("Title").item(0).getFirstChild().getNodeValue();
			String pageHeader = d.getElementsByTagName("PageHeader").item(0).getFirstChild().getNodeValue();
			String pageFooter = d.getElementsByTagName("PageFooter").item(0).getFirstChild().getNodeValue();

			if (title==null || title=="") title=" ";
			if (pageHeader==null || pageHeader=="") pageHeader=" ";
			if (pageFooter==null || pageFooter=="") pageFooter=" ";

			userprofile.setEmail(email);
			userprofile.setUserLastName(lastname);
			userprofile.setUserFirstName(firstname);
			userprofile.setTitle(title);
			userprofile.setPageHeader(pageHeader);
			userprofile.setPageFooter(pageFooter);
			
			try{
			
				String format = null;
				format = d.getElementsByTagName("Format").item(0).getFirstChild().getNodeValue();
				
				if(format!=null){
					if(format.equals("")){
						format = "pdf";
					}
				}
				else{
					format = "pdf";
				}
				userprofile.setFormat(format);
			
			}
			catch(Exception e){
				e.printStackTrace();
				userprofile.setFormat("pdf");
			}
			
			NodeList nl = d.getElementsByTagName("Section");
			
			for (int i = 0; i < nl.getLength(); i++) {
			
				MySection section = new MySection();
				
				for (int e = 0; e < ReportSettings.MAX_CHARTS_PER_PAGE; e++) {
				
   				if (d.getElementsByTagName("SectionTitle_" + e).item(i + count) != null) 
   				{
   					String sectionTitle = d.getElementsByTagName("SectionTitle_" + e).item(i + count).getFirstChild().getNodeValue();
   					section.setSectionTitle(sectionTitle, e);
   				} 
   				else 
   				{
   					section.setSectionTitle(" ", e);
   				}
   
   				if (d.getElementsByTagName("SectionImage_" + e).item(i) != null) 
   				{
   					String image = d.getElementsByTagName("SectionImage_" + e).item(i).getFirstChild().getNodeValue();
   					section.setSectionImage(image, e);
   				} else 
   				{
   					section.setSectionImage(" ", e);
   				}
   
   				if (d.getElementsByTagName("SectionDrillParameters_" + e).item(i) != null) 
   				{
   					String drillparams = d.getElementsByTagName("SectionDrillParameters_" + e).item(i).getFirstChild().getNodeValue();
   					section.setSectionDrillParameters(drillparams, e);
   				} else 
   				{
   					section.setSectionDrillParameters(" ", e);
   				}
   				if (d.getElementsByTagName("SectionDescription_" + e).item(i) != null) 
   				{
   					String description = d.getElementsByTagName("SectionDescription_" + e).item(i).getFirstChild().getNodeValue();
   					section.setSectionDescription(description, e);
   				} else 
   				{
   					section.setSectionDescription(" ", e);
   				}

            }//for (int e = 0; e < ReportSettings.MAX_CHARTS_PER_PAGE; e++)
            
				al.add(section);

			}//for (int i = 0; i < nl.getLength(); i++)
			
			userprofile.setSections(al);
			
		} 
		catch (Exception e) {
			e.printStackTrace();
		}
		
		return userprofile;

	}//parse

}//XmlParser
