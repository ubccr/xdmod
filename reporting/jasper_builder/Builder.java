/*

Author: Lizhe Wang
Date: Dec 1 2010
   
Last Updated: May 4, 2012

*/

public class Builder {

	public static  void main(String args[]) {
	
		String inputfile = "";
		String outputfile = "";
		String inputdir = ""; 
		String outputdir = "";
		String templatedir = "";
		String template = "";

		boolean isOk = true;
		boolean flag = false;

		if (args != null) {
		
			if (args[0] != null) 
				inputdir = args[0];
			else 
				isOk = false;

			if (args[1] != null)
				inputfile = args[1]+".xml";
			else 
				isOk = false;

			if (args[2] != null) 
				outputdir = args[2];
			else 
				isOk = false;
			if (args[3] != null) 
				outputfile = args[3];
			else 
				isOk = false;
			if (args[4] != null) 
				templatedir = args[4];
			else 
				isOk = false;
			if (args[5] != null) 
				template = args[5];
			else 
				isOk = false;

		}//if (args != null)
		
		if (isOk) {
		
			try {
				
				Translate tran = new Translate(inputdir, inputfile,outputdir, outputfile, templatedir, template);
				if(tran.report.getFormat().toLowerCase().equals("pdf")){
					flag = tran.translateToPDF();
				}
				else if(tran.report.getFormat().toLowerCase().equals("xls")){
					flag = tran.translateToXLS();
				}
				else if(tran.report.getFormat().toLowerCase().equals("html")){
					flag = tran.translateToHTML();
				}
				else if(tran.report.getFormat().toLowerCase().equals("doc")){
					flag = tran.translateToDoc();
				}
				else if(tran.report.getFormat().toLowerCase().equals("pptx")){
					flag = tran.translateToPptx();
				}
				else{
               //System.out.println("PDF");	
					flag = tran.translateToPDF();

				}
				
			} 
			catch (Exception e) {
				e.printStackTrace();
			}

		} 
		else {
			System.out.println("error");
		}

	}//main
	
}//Builder
