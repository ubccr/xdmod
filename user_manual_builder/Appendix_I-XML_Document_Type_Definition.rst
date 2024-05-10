Appendix I-XML Document Type Definition
==========================================

<!DOCTYPE xdmod-xml-dataset [

<!ELEMENT header (title, parameters?, start, end, columns)>

<!ELEMENT rows (row?)>

<!ELEMENT parameters (parameter)>

<!ELEMENT parameter (name, value)>

<!ELEMENT row (cell)>

<!ELEMENT cell (column, value)>

<!ELEMENT title (#PCDATA)>

<!ELEMENT name (#PCDATA)>

<!ELEMENT value (#PCDATA)>

<!ELEMENT column (#PCDATA)>

]>
