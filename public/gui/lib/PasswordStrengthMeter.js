/* ************************************************************
Created: 20060120
Author:  Steve Moitozo <god at zilla dot us> -- geekwisdom.com
Description: This is a quick and dirty password quality meter
         written in JavaScript so that the password does
         not pass over the network.
License: MIT License (see below)
Modified: 20060620 - added MIT License
Modified: 20061111 - corrected regex for letters and numbers
                     Thanks to Zack Smith -- zacksmithdesign.com
---------------------------------------------------------------
Copyright (c) 2006 Steve Moitozo <god at zilla dot us>

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or
sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

   The above copyright notice and this permission notice shall
be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
OR OTHER DEALINGS IN THE SOFTWARE.
---------------------------------------------------------------


Password Strength Factors and Weighings

password length:
level 0 (3 point): less than 4 characters
level 1 (6 points): between 5 and 7 characters
level 2 (12 points): between 8 and 15 characters
level 3 (18 points): 16 or more characters

letters:
level 0 (0 points): no letters
level 1 (5 points): all letters are lower case
level 2 (7 points): letters are mixed case

numbers:
level 0 (0 points): no numbers exist
level 1 (5 points): one number exists
level 1 (7 points): 3 or more numbers exists

special characters:
level 0 (0 points): no special characters
level 1 (5 points): one special character exists
level 2 (10 points): more than one special character exists

combinatons:
level 0 (1 points): letters and numbers exist
level 1 (1 points): mixed case letters
level 1 (2 points): letters, numbers and special characters
                    exist
level 1 (2 points): mixed case letters, numbers and special
                    characters exist


NOTE: Because I suck at regex the code might need work

NOTE: Instead of putting out all the logging information,
      the score, and the verdict it would be nicer to stretch
      a graphic as a method of presenting a visual strength
      guage.

************************************************************ */
function testPassword(passwd)
{
        var intScore   = 0;
        var strVerdict = "weak";
        var strLog     = "";

        // PASSWORD LENGTH
        if (passwd.length<5)                         // length 4 or less
        {
            intScore = (intScore+3);
            strLog   = strLog + "3 points for length (" + passwd.length + ")\n";
        }
        else if (passwd.length>4 && passwd.length<8) // length between 5 and 7
        {
            intScore = (intScore+6);
            strLog   = strLog + "6 points for length (" + passwd.length + ")\n";
        }
        else if (passwd.length>7 && passwd.length<16)// length between 8 and 15
        {
            intScore = (intScore+12);
            strLog   = strLog + "12 points for length (" + passwd.length + ")\n";
        }
        else if (passwd.length>15)                    // length 16 or more
        {
            intScore = (intScore+18);
            strLog   = strLog + "18 point for length (" + passwd.length + ")\n";
        }


        // LETTERS (Not exactly implemented as dictacted above because of my limited understanding of Regex)
        if (passwd.match(/[a-z]/))                              // [verified] at least one lower case letter
        {
            intScore = (intScore+1);
            strLog   = strLog + "1 point for at least one lower case char\n";
        }

        if (passwd.match(/[A-Z]/))                              // [verified] at least one upper case letter
        {
            intScore = (intScore+5);
            strLog   = strLog + "5 points for at least one upper case char\n";
        }

        // NUMBERS
        if (passwd.match(/\d+/))                                 // [verified] at least one number
        {
            intScore = (intScore+5);
            strLog   = strLog + "5 points for at least one number\n";
        }

        if (passwd.match(/(.*[0-9].*[0-9].*[0-9])/))             // [verified] at least three numbers
        {
            intScore = (intScore+5);
            strLog   = strLog + "5 points for at least three numbers\n";
        }


        // SPECIAL CHAR
        if (passwd.match(/.[!,@#$%\^&*?_~]/))            // [verified] at least one special character
        {
            intScore = (intScore+5);
            strLog   = strLog + "5 points for at least one special char\n";
        }

                                     // [verified] at least two special characters
        if (passwd.match(/(.*[!,@#$%\^&*?_~].*[!,@#$%\^&*?_~])/))
        {
            intScore = (intScore+5);
            strLog   = strLog + "5 points for at least two special chars\n";
        }


        // COMBOS
        if (passwd.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/))        // [verified] both upper and lower case
        {
            intScore = (intScore+2);
            strLog   = strLog + "2 combo points for upper and lower letters\n";
        }

        if (passwd.match(/([a-zA-Z])/) && passwd.match(/([0-9])/)) // [verified] both letters and numbers
        {
            intScore = (intScore+2);
            strLog   = strLog + "2 combo points for letters and numbers\n";
        }

                                    // [verified] letters, numbers, and special characters
        if (passwd.match(/([a-zA-Z0-9].*[!,@#$%\^&*?_~])|([!,@#$%\^&*?_~].*[a-zA-Z0-9])/))
        {
            intScore = (intScore+2);
            strLog   = strLog + "2 combo points for letters, numbers and special chars\n";
        }

      if (passwd.length == 0)
      {
         strVerdict = "password not specified";
         clsIndicator = "veryweak";
      }
        else if (intScore < 16)
        {
           strVerdict = "very weak";
           clsIndicator = "veryweak";
        }
        else if (intScore > 15 && intScore < 25)
        {
           strVerdict = "weak";
           clsIndicator = "weak";
        }
        else if (intScore > 24 && intScore < 35)
        {
           strVerdict = "mediocre";
           clsIndicator = "mediocre";
        }
        else if (intScore > 34 && intScore < 45)
        {
           strVerdict = "strong";
           clsIndicator = "strong";
        }
        else
        {
           strVerdict = "stronger";
           clsIndicator = "strong";
        }

      return {'verdict' : strVerdict, 'indicator' : clsIndicator};

}//testPassword
