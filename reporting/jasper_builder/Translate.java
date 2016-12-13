/*

Author: Lizhe Wang
Date: Dec 1 2010
   
Last Updated: May 4, 2012

*/

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.InputStreamReader;
import java.util.HashMap;
import net.sf.jasperreports.engine.JRException;
import net.sf.jasperreports.engine.JRExporterParameter;
import net.sf.jasperreports.engine.JasperCompileManager;
import net.sf.jasperreports.engine.JasperFillManager;
import net.sf.jasperreports.engine.JasperPrint;
import net.sf.jasperreports.engine.JasperReport;
import net.sf.jasperreports.engine.JasperRunManager;
import net.sf.jasperreports.engine.export.JExcelApiExporter;
import net.sf.jasperreports.engine.export.JRCsvExporter;
import net.sf.jasperreports.engine.export.JRXhtmlExporter;
import net.sf.jasperreports.engine.export.JRXlsExporter;
import net.sf.jasperreports.engine.export.JRXlsExporterParameter;
import net.sf.jasperreports.engine.export.JRHtmlExporter;
import net.sf.jasperreports.engine.export.JRHtmlExporterParameter;
import net.sf.jasperreports.engine.export.JRPdfExporter;
import net.sf.jasperreports.engine.export.JRRtfExporter;
import net.sf.jasperreports.engine.export.JRXmlExporter;
import net.sf.jasperreports.engine.export.oasis.JROdtExporter;
import net.sf.jasperreports.engine.export.ooxml.JRDocxExporter;
import net.sf.jasperreports.engine.export.ooxml.JRPptxExporter;
import net.sf.jasperreports.engine.export.ooxml.JRXlsxExporter;
import net.sf.jasperreports.engine.query.JRXPathQueryExecuterFactory;
import net.sf.jasperreports.engine.util.JRLoader;
import net.sf.jasperreports.engine.util.JRXmlUtils;
import net.sf.jasperreports.engine.util.JRLoader;
import net.sf.jasperreports.engine.util.AbstractSampleApp;
import net.sf.jasperreports.engine.JasperExportManager;
import net.sf.jasperreports.engine.JasperPrint;
import net.sf.jasperreports.engine.JasperPrintManager;

public class Translate {

	private String inputfile = null;
	private String inputdir =null ;
	private String outputdir =null ;
	private String outputfile =null ;
	private String templatedir = null;
	private String template = null;

	public Report report = null;

	private JasperReport jasperReport;
	private JasperPrint jasperPrint = null;
	private File reportFile = null;
	private MyDataSource cds = null;
	private String position;
	HashMap<String, Object> arg = null;

   // ---------------------------------------------
   
	public Translate(String inputdir, String inputfile, String outputdir, String outputfile, String templatedir, String template) {

		this.inputdir=inputdir;
		this.outputdir = outputdir;
		this.inputfile = inputfile;
		this.outputfile = outputfile;
		this.templatedir = templatedir;
		this.template = template;

		report = XmlParser.parse(inputdir+"/"+inputfile);

		try {
         JasperCompileManager.compileReportToFile(templatedir+"/"+template+".jrxml", templatedir+"/"+template+".jasper");
      }
      catch (JRException e1) {
         e1.printStackTrace(); 
		}
		
		reportFile = new File(templatedir+"/"+template+".jasper");
		cds = new MyDataSource(report);
		arg = new HashMap<String, Object>();
		arg.put("title", report.getTitle());
		arg.put("user", report.getUserFirstName()+ " " + report.getUserLastName());
		arg.put("email", report.getEmail());
		arg.put("pageHeader", report.getPageHeader());
		arg.put("pageFooter", report.getPageFooter());

		try {
		
			jasperReport = (JasperReport) JRLoader.loadObject(reportFile.getPath());
			jasperPrint = JasperFillManager.fillReport(jasperReport, arg, cds);
			JasperFillManager.fillReportToFile(jasperReport, templatedir+"/"+template+".jrprint", arg, cds);
		
		}
		catch (Exception e) {
			e.printStackTrace();
		}

	}//Translate

   // ---------------------------------------------
   	
	public boolean translateToPDF() throws JRException {

		boolean flag = false;
		position = outputdir + "/"+outputfile + ".pdf";
		JRPdfExporter pdfExporter = new JRPdfExporter();
		pdfExporter.setParameter(JRExporterParameter.JASPER_PRINT, jasperPrint);
		pdfExporter.setParameter(JRExporterParameter.OUTPUT_FILE_NAME, position);

		try {
		
         pdfExporter.exportReport();
         flag = true;
         
		}
		catch (JRException e) {
			e.printStackTrace();
		}

		return flag;

	}//translateToPDF

   // ---------------------------------------------
   
   public boolean translateToDoc() throws JRException {
   
      boolean flag = false;
      position = outputdir + "/"+outputfile + ".doc";
      JRDocxExporter docxExporter = new JRDocxExporter();
      docxExporter.setParameter(JRExporterParameter.JASPER_PRINT, jasperPrint);
      docxExporter.setParameter(JRExporterParameter.OUTPUT_FILE_NAME, position);
      
      try {
      
         docxExporter.exportReport();
         flag = true;
         
      }
      catch (JRException e) {
         e.printStackTrace();
      }
      
      return flag;

   }//translateToDoc

   // ---------------------------------------------
   
   public boolean translateToPptx() throws JRException {
   
      boolean flag = false;
      position = outputdir + "/"+outputfile + ".pptx";
      JRPptxExporter pptxExporter = new JRPptxExporter();
      pptxExporter.setParameter(JRExporterParameter.JASPER_PRINT, jasperPrint);
      pptxExporter.setParameter(JRExporterParameter.OUTPUT_FILE_NAME, position);
      
      try {
      
         pptxExporter.exportReport();
         flag = true;
         
      }
      catch (JRException e) {
         e.printStackTrace();
      }
      
      return flag;
           
   }//translateToPptx

   // ---------------------------------------------
   
	public boolean translateToXLS() {
	
      boolean flag = false;
		position = outputdir + "/"+outputfile + ".xls";
		
		try {
		
         File in = new File(inputfile);
         File out = new File(position);
         XMLToExcel excel = new XMLToExcel();
         excel.generateExcel(in, out);
		    
		}
		catch (Exception e) {
			e.printStackTrace();
		}
		
		return flag;
		
	}//translateToXLS

   // ---------------------------------------------
   
	public boolean translateToHTML() {
	
		boolean flag = false;
		position = inputfile.substring(0, inputfile.indexOf("."))+ ".html";

		try {

			JRHtmlExporter htmlExporter = new JRHtmlExporter();
			htmlExporter.setParameter(JRExporterParameter.JASPER_PRINT, jasperPrint);
			htmlExporter.setParameter(JRExporterParameter.OUTPUT_FILE_NAME, position);
			htmlExporter.setParameter( JRHtmlExporterParameter.IS_USING_IMAGES_TO_ALIGN, Boolean.TRUE);
			htmlExporter.setParameter( JRHtmlExporterParameter.IS_OUTPUT_IMAGES_TO_DIR, Boolean.TRUE);
			htmlExporter.setParameter(JRHtmlExporterParameter.SIZE_UNIT, JRHtmlExporterParameter.SIZE_UNIT_POINT);
			
			try {
			
            htmlExporter.exportReport();
				flag = true;
				
			} catch (JRException e) {
				e.printStackTrace();
			}
			
		}
		catch (Exception e) {
			e.printStackTrace();
		}

		return flag;
		
	}//translateToHTML

}//Translate
