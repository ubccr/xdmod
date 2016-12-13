/* 
Author: Lizhe Wang
Date: 12/18/2010
*/

import org.apache.poi.hssf.usermodel.*;
import org.apache.xpath.NodeSet;
import org.w3c.dom.*;
import org.xml.sax.InputSource;
import java.io.*;
import javax.xml.xpath.*;
import org.w3c.dom.Document;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import org.w3c.dom.NodeList;

public class XMLToExcel{
private int section_num;
private int column_num;
public void generateExcel(File in, File out) 
{/* start of generateExcel */
	DocumentBuilderFactory f = DocumentBuilderFactory.newInstance();
	
	try{
		HSSFWorkbook wb = new HSSFWorkbook();

		DocumentBuilder builder = f.newDocumentBuilder();
		Document d = builder.parse(in);
		NodeList nl = d.getElementsByTagName("Section");
		
		HSSFCellStyle cellStyle = wb.createCellStyle();
		cellStyle.setBorderRight(HSSFCellStyle.BORDER_MEDIUM);
		cellStyle.setBorderTop(HSSFCellStyle.BORDER_MEDIUM);   

		HSSFSheet firstSheet = wb.createSheet("Overview");
		firstSheet.setColumnWidth((short) 0, (short) (250 * 50));
		firstSheet.setColumnWidth((short) 1, (short) (250 * 50));
                HSSFRow R = firstSheet.createRow(( short) 0);
 		HSSFCell C = R.createCell((short) 0);
                C.setCellValue("Report Title");
		C.setCellStyle(cellStyle);
 		C = R.createCell((short) 1);
                C.setCellValue( d.getElementsByTagName("Title").item(0).getFirstChild().getNodeValue());        
		C.setCellStyle(cellStyle);

                R = firstSheet.createRow(( short) 1);
                C = R.createCell((short) 0);
                C.setCellValue( d.getElementsByTagName("FirstName").item(0).getFirstChild().getNodeValue());
                C.setCellStyle(cellStyle);
                C = R.createCell((short) 1);
                C.setCellValue( d.getElementsByTagName("LastName").item(0).getFirstChild().getNodeValue());
                C.setCellStyle(cellStyle);
		C = R.createCell((short) 2);
                C.setCellValue( d.getElementsByTagName("Email").item(0).getFirstChild().getNodeValue());
                C.setCellStyle(cellStyle);




		int j=2;

		for (int i = 0; i < nl.getLength(); i++) 
		{
			
			HSSFSheet spreadSheet = wb.createSheet(d.getElementsByTagName("SectionTitle").item(i).getFirstChild().getNodeValue());
			//System.out.println(d.getElementsByTagName("SectionTitle").item(i).getFirstChild().getNodeValue());



			NodeList child_nl = nl.item(i).getChildNodes();

			HSSFRow firstrow = spreadSheet.createRow(( short) 0);        
			for(int ii = 0 ; ii<child_nl.getLength() ; ii++) 
			{
				if(child_nl.item(ii).getNodeName() == "SectionTitle")
				{
					R = firstSheet.createRow(( short) j);				
					C = R.createCell((short) 0);
					C.setCellValue(child_nl.item(ii).getFirstChild().getNodeValue());
			                C.setCellStyle(cellStyle);
				}

				if(child_nl.item(ii).getNodeName() == "SectionDescription")
				{
					C = R.createCell((short) 1);
					C.setCellValue(child_nl.item(ii).getFirstChild().getNodeValue());
			                C.setCellStyle(cellStyle);
					j++;
				}

				if(child_nl.item(ii).getNodeName() == "SectionTable")
				{
					NodeList cc_nl=child_nl.item(ii).getChildNodes();	
					int iiii=0;
					int jjjj=0;
					for ( int iii = 0; iii < cc_nl.getLength(); iii++)
					{
						String nd = cc_nl.item(iii).getNodeName();
						//System.out.println(iii+nd);
						if(nd == "TableHeader")
						{
						}

						if(nd == "ColumnHeader")
						{
							spreadSheet.setColumnWidth((short) iiii, (short) (250 * 50));
							HSSFCell cell = firstrow.createCell((short) iiii);
							cell.setCellValue( cc_nl.item(iii).getFirstChild().getNodeValue());        
							cell.setCellStyle(cellStyle);        
							iiii=iiii+1;
						}
						if(nd == "Row")
						{
							HSSFRow	 row = spreadSheet.createRow(( short) jjjj+1);    
							NodeList ccc_nl=cc_nl.item(iii).getChildNodes();
							int kk=0;
							for ( int iiiii = 0; iiiii < ccc_nl.getLength() ; iiiii++) 
							{
								if (ccc_nl.item(iiiii).getNodeName() == "Column")
								{
								HSSFCell cell = row.createCell((short) kk);
								cell.setCellValue( (ccc_nl.item(iiiii)).getFirstChild().getNodeValue());        
								//System.out.println (ccc_nl.item(iiiii).getFirstChild().getNodeValue());        
								cell.setCellStyle(cellStyle);        
								kk=kk+1;
								}
							}
							jjjj=jjjj+1;

						}


					}
				}
			}
			
 			/*
			
			NodeList row_nl = d.getElementsByTagName("Row");
			for (int ii = 0; ii < row_nl.getLength(); ii++) 
			{ 
 				HSSFRow	 row = spreadSheet.createRow(( short) ii+1);        
				
				for ( int iii = 0; iii < column_num; iii++) 
				{
					HSSFCell cell = row.createCell((short) iii);
 					cell.setCellValue(((Element) ( row_nl.item(ii))).getElementsByTagName("Column").item(iii).getFirstChild().getNodeValue());        
					cell.setCellStyle(cellStyle);        
				}
			}  
			*/
			
			
		}/* end of for section*/
		
		FileOutputStream output = new FileOutputStream(out);
		wb.write(output);
		output.flush();
		output.close();	
		
		
	}catch (Exception e) {
	}


}  /* end of generateExcel */
/* start of generateExcel */


/* begin of main
public static void main(String[] argv) 
{
    File in = new File(argv[0]);    
    File out = new File(argv[1]);    
    XMLToExcel excel = new XMLToExcel();
    excel.generateExcel(in, out);
}
 end of main */

}/* end of XML2EXCEL */

