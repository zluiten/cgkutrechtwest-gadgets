<?xml version='1.0' encoding='UTF-8'?>
<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' version='1.0'> 
    <xsl:output method="html" version='4.0' encoding='UTF-8' indent='yes'/>
    <xsl:template match='/'>
        <script type="text/javascript"> <![CDATA[
            function csvListExplode(fieldId,csvList)
            {
                a = csvList.split(',');
                e = document.getElementById(fieldId);
                var out = '';
                for(i=0;i<a.length;i++)
                    out = out + a[i] +'<BR/>';
                    
                e.innerHTML = out;
            }

            function calcDateDifference(fieldId,newMysqlDate,oldMysqlDate)
            {
                a = newMysqlDate.split('-');
                now = new Date(a[1]+'/'+a[2]+'/'+a[0]);
                a = oldMysqlDate.split('-');
                then = new Date(a[1]+'/'+a[2]+'/'+a[0]);
                
                diff = now.getTime()-then.getTime();
                
                var one_day = 1000*60*60*24;
                var one_year = one_day*365;
                var one_month = one_day*30.5;
                var y = Math.floor(diff/one_year);
                diff -= y*one_year;
                var m = Math.floor(diff/one_month);
                document.getElementById(fieldId).innerHTML = ''+y+' year(s) '+m+' month(s)';
            }
            
            function fixNumber(fieldId,number)
            {
                ret = '';
                while(number.length>3)
                {
                    ret = number.slice(number.length-3) +' '+ ret;
                    number = number.slice(0,number.length-3);
                }
                
                ret = number +' '+ ret;
                document.getElementById(fieldId).innerHTML = ret;
            }
 
            ]]>
        </script>
        <style type="text/css">
            body { text-align: left; font-family: Tahoma, sans-serif; font-size: 12px; }
            .table { width:100%; border: solid 1px #AAA; border-collapse: collapse; }
            .table th { border: solid 1px #AAA; vertical-align: top; padding: 2px; background-color: #EEE; }
            .table td { width: 50%; border: solid 1px #AAA; vertical-align: top; padding: 2px; font-size: 12px; }
            .content { margin-left:0.5cm; }
            
            .address-line { display: block; }
            
            .field-label { font-weight: bold; font-size: 10px; font-family: sans-serif;}
            .entry-label { float: left; width: 2cm;}
            .entry-value {  }
            
            .extable { width:100%; border: solid 1px #AAA; border-collapse: collapse; }
            .extable th { border: solid 1px #AAA; vertical-align: top; padding: 2px; background-color: #EEE; }
            .extable td { border: solid 1px #AAA; vertical-align: top; padding: 2px; font-size: 12px; }
            .experience-date { vertical-align: top; font-weight: bold; border: solid 1px #AAA; }
            .experience-text { white-space: pre; }
            .experience-textarea { width: 100%; height:150px; font-family: Verdana, sans-serif; font-size: smaller; }
            
            .changelog table { width: 100%; font-family: Verdana, sans-serif; font-size: x-small; }
        </style>
        <br/><br/>
        <table class='table'>
            <tr>
                <td align='center' style='border: solid 1px #AAA; font-size:18px;'><xsl:value-of select='/contact/fullname'/></td>
                <td><span class='field-label'>Project Country</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Project Country"]/value'/></div>
                    </div>
                </td>
            </tr>
            <tr>           
                <td><span class='field-label'>SWARCO company</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Applicant"]/value'/></div>
                    </div>
                </td>
                <td><span class='field-label'>SWARCO partner or joint venture</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Project Partner"]/value'/></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td><span class='field-label'>Name and Address of Employer</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Name of Employer"]/value'/></div>
                        <xsl:apply-templates select="/contact/address-list/address[type='Employer Address']"/>
                    </div>
                </td>               
                <td><span class='field-label'>Contact Person</span>
                    <div class='content'>
                        <div class='entry-label'>Name:</div>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Superior"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Phone:</div>
                        <div class='entry-value'><xsl:value-of select='/contact/phone-list/phone[label="phone"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Mobile:</div>
                        <div class='entry-value'><xsl:value-of select='/contact/phone-list/phone[label="mobile"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Fax:</div>
                        <div class='entry-value'><xsl:value-of select='/contact/phone-list/phone[label="fax"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Email:</div>
                        <div class='entry-value'><xsl:value-of select='/contact/email-list/email[label="email"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Website:</div>
                        <div class='entry-value'><xsl:value-of select='/contact/www-list/www[label="www"]/value'/>&#x00A0;</div>
                    </div>
                </td>
                
            </tr>
            <tr>
                <!-- CONTACTS -->
                <td><span class='field-label'>Contract Role</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Contract Role"]/value'/></div>
                    </div>                    
                </td>
                <td><span class='field-label'>SWARCO Project Category</span>
                    <div id='calculation2' class='content'>
                        <script type="text/javascript"> 
                            csvListExplode('calculation2',"<xsl:value-of select='/contact/other-list/other[label="Project Category"]/value'/>");
                        </script>
                    </div>
                </td>
            </tr>
            <tr>
                <td><span class='field-label'>Value of the total contract (EUR)</span>
                    <div class='content'>
                        <div id='calculation878' class='entry-value'>
                        <script type="text/javascript"> 
                            fixNumber('calculation878',"<xsl:value-of select='/contact/other-list/other[label="Total Value"]/value'/>");
                        </script>
                        </div>
                    </div>
                </td>               
                <td><span class='field-label'>Value of SWARCO Part (EUR)</span>
                    <div class='content'>
                        <div id='calculation879' class='entry-value'>
                            <script type="text/javascript"> 
                                fixNumber('calculation879',"<xsl:value-of select='/contact/other-list/other[label="SWARCO Value"]/value'/>");
                            </script>
                        </div>
                    </div>
                </td>                     
            </tr>
            <tr>
                <td><span class='field-label'>Date of award</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/date-list/date[label="Awarded"]/value1'/></div>
                    </div>
                </td>               
                <td><span class='field-label'>Date of completion</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/date-list/date[label="Completed"]/value1'/></div>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                </td>               
                <td><span class='field-label'>Contract / subcontract duration</span>
                    <div id='calculation1' class='content'>
                        <script type="text/javascript"> 
                            calcDateDifference('calculation1',"<xsl:value-of select='/contact/date-list/date[label="Completed"]/value1'/>","<xsl:value-of select='/contact/date-list/date[label="Awarded"]/value1'/>");
                        </script>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan='2'><span class='field-label'>Nature of works and special features</span>
                    <div class='content'>
                        <div class='experience-text'><xsl:value-of select='/contact/notes'/></div>
                    </div>
                </td>               
            </tr>           
        </table>
        <br/>
        <!-- RELATIONS TABLE: Plugin generated XML-->
        <table class='extable'>
            <tr>
                <th colspan='2' class='field-label'><i>Links to persons who are/were involved in this project:</i></th>
            </tr>
            <tr>
                <th>Name</th>
                <th>Position</th>
            </tr>
            <xsl:for-each select='/contact/relationships/relationship'>
                <tr>
                    <td class=''><xsl:copy-of select='relatedTo/*'/></td>
                    <td class=''><xsl:value-of select='description'/></td>
                </tr>
            </xsl:for-each>
        </table>        
        <br/><br/>
        <div class='changelog'><xsl:copy-of select='/contact/changelog/*'/></div>
        <br/><br/>
    </xsl:template>
    
    <!-- ADDRESS OUTPUT TEMPLATE -->
    <xsl:template match="address">
        <xsl:copy-of select='formatted'/>
    </xsl:template>
</xsl:stylesheet>
