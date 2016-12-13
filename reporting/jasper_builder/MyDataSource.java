/*

Author: Lizhe Wang
Date: Dec 1 2010

Last Updated: May 4, 2012

MyDataSource is executed against a Jasper Report Template XML file (.jrxml)

*/

import net.sf.jasperreports.engine.JRDataSource;
import net.sf.jasperreports.engine.JRException;
import net.sf.jasperreports.engine.JRField;

public class MyDataSource implements JRDataSource
{

	private Report report;
   private int index = -1;

   // ---------------------------------------------
   	
	public MyDataSource(Report report)
	{
		this.report = report;
	}
   
   // ---------------------------------------------
   	
	public boolean next() throws JRException
	{
		index++;
		return (index < report.getSections().size());
	}

   // ---------------------------------------------
   
	public Object getFieldValue(JRField field) throws JRException 
	{
	
		Object value = null;
		String fieldName = field.getName();

		for (int entryIndex = 0; entryIndex <= ReportSettings.MAX_CHARTS_PER_PAGE; entryIndex++) {
		
   		if (("Section_Description_" + entryIndex).equals(fieldName))
   		{
            value = (String)report.getSections().get(index).getSectionDescription(entryIndex).trim();
   		}
   		else if (("Section_Drill_Parameters_" + entryIndex).equals(fieldName))
   		{
            value = (String)report.getSections().get(index).getSectionDrillParameters(entryIndex).trim();
   		}
   		else if (("Section_Image_" + entryIndex).equals(fieldName))
   		{
            value = (String)report.getSections().get(index).getSectionImage(entryIndex).trim();
   		}
   		else if (("Section_Title_" + entryIndex).equals(fieldName))
   		{
            value = (String)report.getSections().get(index).getSectionTitle(entryIndex).trim();
   		}
		
		}//for

      return value;

	}//getFieldValue
	
}//MyDataSource
