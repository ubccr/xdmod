/*

Author: Lizhe Wang
Date: Dec 1 2010
   
Last Updated: May 4, 2012

*/

public class MySection {

	private String[] sectionTitle;
	private String[] sectionDrillParameters;
	private String[] sectionDescription;
	private String[] sectionImage;

   // ---------------------------------------------

   public MySection () {

      sectionTitle = new String[ReportSettings.MAX_CHARTS_PER_PAGE];
      sectionDrillParameters = new String[ReportSettings.MAX_CHARTS_PER_PAGE];
      sectionDescription = new String[ReportSettings.MAX_CHARTS_PER_PAGE];
      sectionImage = new String[ReportSettings.MAX_CHARTS_PER_PAGE];

   }//MySection

   // ---------------------------------------------
   
	public String getSectionTitle(int slot) {
		return sectionTitle[slot];
	}

   // ---------------------------------------------
   
	public void setSectionTitle(String sectionTitle, int slot) {
		this.sectionTitle[slot] = sectionTitle;
	}

   // ---------------------------------------------
   
	public String getSectionDescription(int slot) {
		return sectionDescription[slot];
	}

   // ---------------------------------------------
   
	public void setSectionDescription(String sectionDescription, int slot) {
		this.sectionDescription[slot] = sectionDescription;
	}

   // ---------------------------------------------
   
	public String getSectionDrillParameters(int slot) {
      return sectionDrillParameters[slot];
	}

   // ---------------------------------------------
   
	public void setSectionDrillParameters(String sectionDrillParameters, int slot) {
		this.sectionDrillParameters[slot] = sectionDrillParameters;
	}

   // ---------------------------------------------
   
	public String getSectionImage(int slot) {
		return sectionImage[slot];
	}

   // ---------------------------------------------
   
	public void setSectionImage(String sectionImage, int slot) {
		this.sectionImage[slot] = sectionImage;
	}
	
}//MySection
