#!/bin/bash
#---------------------------------------------------------------------------------
# Author: Ryan Gentner
# Tasks: Set Class Path, Compile Java code, Execute Report Builder
# Updated: 6/10/2013
#---------------------------------------------------------------------------------

PATH=$PATH:/opt/java/bin/

#java -version
#java version "1.7.0_13"
#OpenJDK Runtime Environment (IcedTea7 2.3.6) (7u13-2.3.6-0ubuntu0.12.04.1)
#OpenJDK 64-Bit Server VM (build 23.7-b01, mixed mode)

function getSetting {
   # This settings file path is replaced during the (open source)
   # installation process, so make sure that works if this is ever
   # changed.
   DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
   SETTINGS="$DIR/../../configuration/portal_settings.ini"

   if ! [ -e "$SETTINGS" ]; then
      return
   fi

   SECTION=$1
   PARAM=$2

   sed -n -e "/\[$SECTION\]/,/\[/ p" $SETTINGS | grep $PARAM | sed "s/$PARAM = \(['\"]\)\(.*\)\1/\2/"
}

# ==================================================================

JAVA=$(getSetting "reporting" "java_path")
JAVAC=$(getSetting "reporting" "javac_path")

# Use defaults on PATH if there is no configuration file or no values were set.
JAVA="${JAVA:-java}"
JAVAC="${JAVAC:-javac}"

BASEPATH="$(cd "$(dirname "$0")" && pwd -P)"

# ==================================================================

displayUsage() {

   echo "Invalid arguments"
   echo "Usage: ReportBuilder.sh [options]"
   echo "-C : compile java source code"
   echo "-W : working directory (the temporary space used for the build)"
   echo "-E : execute report builder, it requires the following arguments:"
   echo "          -W + working directory (source and destination, including template files)"
   echo "          -B + base filename (without file type extension, .xml, .pdf, .docx, …) "
   echo "          -T + JasperReport template file name (without file type extension, .jrxml)"
   echo "          -F + Report Font (if not specified, defaults to 'Arial')"
   
   exit
   
}

# Set up java classpath string =====================================

   path="$BASEPATH/"
   path="$path:$BASEPATH/lib/commons-beanutils/commons-beanutils-1.8.0.jar"
   path="$path:$BASEPATH/lib/commons-logging/commons-logging.jar"
   path="$path:$BASEPATH/lib/jasperreports/jasperreports-3.7.6.jar"
   path="$path:$BASEPATH/lib/commons-collections/commons-collections-2.1.1.jar"
   path="$path:$BASEPATH/lib/poi/poi-3.6-20091214.jar"
   path="$path:$BASEPATH/lib/commons-digester/commons-digester-1.7.jar"
   path="$path:$BASEPATH/lib/itextpdf/itext-2.1.7.jar"
   path="$path:$BASEPATH/lib/xalan/xalan.jar"

   CLASSPATH=${CLASSPATH}:${path}
   
# Must take parameters =============================================

if [ $# = 0 ]; then
   displayUsage
   exit
fi

# Default values ===================================================

template="template"
inputfile="NULL"
outputfile="NULL" 
execute=0
compile=0
builder_path="."
font="Arial"

# ==================================================================

while getopts "W:B:T:F:CE" OPTION
do

   case $OPTION in
	 
      E)
         execute=1
         ;;
         
      W)
         builder_path=$OPTARG
         inputdir=$OPTARG
         outputdir=$OPTARG
         templatedir=$OPTARG
         ;;
               
      B)
         inputfile=$OPTARG
         outputfile=$OPTARG
         ;;
          
      T)
         template=$OPTARG
         ;;

      F)
         font=$OPTARG
         ;;
                  
      C)
         compile=1
         ;;

      ?)
         displayUsage
         exit
         ;;

   esac
   
done

# Compile (if -C passed in) ========================================

if [ $compile = 1 ]; then

   cd $builder_path

   echo "Attempting to compile JasperReport code..."
   echo "$JAVAC -classpath ${CLASSPATH}  *.java"
   echo
   $JAVAC -classpath ${CLASSPATH}  *.java

   exit

fi

# Execute report builder ===========================================

if [ $execute = 1 ]; then

   
   if [ $inputfile = "NULL" ] || [ $outputfile = "NULL" ]; then
      displayUsage
      exit
   fi

   templatefile="$template.$font"
   cd $builder_path

   echo "Start: `date` `date +%s`" > $outputdir/build_info
   $JAVA -cp ${CLASSPATH} Builder $inputdir $inputfile $outputdir $outputfile $templatedir $templatefile
   echo "End: `date` `date +%s`" >> $outputdir/build_info
   chmod 444 $outputdir/build_info

fi
