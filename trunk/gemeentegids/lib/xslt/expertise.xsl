<?xml version='1.0' encoding='UTF-8'?>
<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' version='1.0'> 
<xsl:output method="html" version='4.0' encoding='UTF-8' indent='yes'/>
    <xsl:template match='/'>
        <script type="text/javascript"> <![CDATA[
            function calcFromToday(fieldId,mysqlDate)
            {
                if(mysqlDate.length != 10)
                {
                    document.getElementById(fieldId).innerHTML = '---';
                    return;
                }
                
                a = mysqlDate.split('-');
                now = new Date();
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
            
            .anyFix { display: inline; margin-right: 5px; font-weight: bold; font-size: 18px;}
            .anyName { display: inline; margin-right: 4px; }
            .address-line { display: block; }
            
            .field-label { font-weight: bold; font-size: 10px; font-family: sans-serif;}
            .entry-label { font-weight: bold; float: left; width: 2cm;}
            .entry-value {  }
            
            .extable { width:100%; border: solid 1px #AAA; border-collapse: collapse; }
            .extable th { border: solid 1px #AAA; vertical-align: top; padding: 2px; background-color: #EEE; }
            .extable td { border: solid 1px #AAA; vertical-align: top; padding: 2px; font-size: 12px; }
            .experience-date { vertical-align: top; font-weight: bold; border: solid 1px #AAA; }
            .experience-text { border: solid 1px #AAA; }
            .experience-textarea { width: 100%; height:150px; font-family: Verdana, sans-serif; font-size: smaller; }
            
            .changelog table { width: 100%; font-family: Verdana, sans-serif; font-size: x-small; }
        </style>
        <br/><br/>
            <table class='table'>
              <tr>
                <th colspan='2' align='center' style='border: solid 1px #AAA; font-size:28px;'>
                    <div class='anyFix'><xsl:value-of select='/contact/name/prefix'/></div>
                    <div class='anyName'><xsl:value-of select='/contact/name/given'/></div>
                    <div class='anyName'><xsl:value-of select='/contact/name/middle'/></div>
                    <div class='anyName'><xsl:value-of select='/contact/name/family'/></div>
                    <div class='anyFix'><xsl:value-of select='/contact/name/suffix'/></div>
                </th>
              </tr>
              <tr>
                  <td>
                      <xsl:if test='/contact/pictureURL != ""'>
                      <img><xsl:attribute name="src"><xsl:value-of select="/contact/pictureURL"/></xsl:attribute></img>
                      </xsl:if>
                  </td>
                  <td><span class='field-label'>Date of Birth</span>
                      <div class='content'>
                         <div class='entry-value'><xsl:value-of select='/contact/date-list/date[label="Date of Birth"]/value1'/></div>
                      </div>
                  </td>               
              </tr>
              <tr>
                  <td><span class='field-label'>Employer Address</span>
                      <div class='content'>
                          <xsl:apply-templates select="/contact/address-list/address[type='Employer Address']"/>
                      </div>
                  </td>               
                  <td><span class='field-label'>Work Address</span>
                      <div class='content'>
                          <xsl:apply-templates select="/contact/address-list/address[type='Work Address']"/>
                      </div>
                  </td>               
              </tr>
              <tr>
                <td><span class='field-label'>Company Position</span>
                    <div class='content'>
                        <xsl:for-each select="/contact/other-list/other[label='Company Position']">
                            <div class='entry-value'><xsl:value-of select='value'/></div>
                        </xsl:for-each>
                    </div>
                 </td>               
                <td><span class='field-label'>Superior</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Superior"]/value'/></div>
                    </div>
                 </td>               
              </tr>
              <tr>
                <td><span class='field-label'>Project Position</span>
                    <div class='content'>
                        <center>
                            <table style="border-collapse: collapse;">
                                <xsl:if test='/contact/date-list/date[label="Project Manager"]/value1'>
                                <tr>
                                    <td style="vertical-align: middle;">Project Manager:</td>
                                    <td>
                                        <div id="da1">
                                            <script type="text/javascript"> 
                                                calcFromToday('da1','<xsl:value-of select='/contact/date-list/date[label="Project Manager"]/value1'/>');
                                            </script>
                                        </div>
                                    </td>
                                </tr>
                                </xsl:if>
                                <xsl:if test='/contact/date-list/date[label="UTC Project Specialist"]/value1'>
                                    <tr>
                                    <td style="vertical-align: middle;">UTC Project Specialist:</td>
                                    <td>
                                        <div id="da2">
                                        <script type="text/javascript"> 
                                            calcFromToday('da2','<xsl:value-of select='/contact/date-list/date[label="UTC Project Specialist"]/value1'/>');
                                        </script>
                                    </div></td>
                                </tr>
                                </xsl:if>
                                <xsl:if test='/contact/date-list/date[label="IUTC Project Specialist"]/value1'>
                                <tr>
                                    <td style="vertical-align: middle;">IUTC Project Specialist:</td>
                                    <td>               <div id="da3">
                                        <script type="text/javascript"> 
                                            calcFromToday('da3','<xsl:value-of select='/contact/date-list/date[label="IUTC Project Specialist"]/value1'/>');
                                        </script>
                                    </div></td>
                                </tr>
                                </xsl:if>
                                <xsl:if test='/contact/date-list/date[label="PT Project Specialist"]/value1'>
                                <tr>
                                    <td style="vertical-align: middle;">PT Project Specialist:</td>
                                    <td>               <div id="da4">
                                        <script type="text/javascript"> 
                                            calcFromToday('da4','<xsl:value-of select='/contact/date-list/date[label="PT Project Specialist"]/value1'/>');
                                    </script>
                                </div></td>
                                </tr>
                                </xsl:if>
                                <xsl:if test='/contact/date-list/date[label="Parking Project Specialist"]/value1'>
                                <tr>
                                    <td style="vertical-align: middle;">Parking Project Specialist:</td>
                                    <td>               <div id="da5">
                                        <script type="text/javascript"> 
                                            calcFromToday('da5','<xsl:value-of select='/contact/date-list/date[label="Parking Project Specialist"]/value1'/>');
                                        </script>
                                    </div></td>
                                </tr>
                                </xsl:if>
                                <xsl:if test='/contact/date-list/date[label="Technical Engineer"]/value1'>
                                <tr>
                                    <td style="vertical-align: middle;">Technical Engineer:</td>
                                    <td>               <div id="da6">
                                        <script type="text/javascript"> 
                                            calcFromToday('da6','<xsl:value-of select='/contact/date-list/date[label="Technical Engineer"]/value1'/>');
                                        </script>
                                    </div></td>
                                </tr>
                                </xsl:if>
                                <xsl:if test='/contact/date-list/date[label="Civil Engineer"]/value1'>
                                <tr>
                                    <td style="vertical-align: middle;">Civil Engineer:</td>
                                    <td>               <div id="da7">
                                        <script type="text/javascript"> 
                                            calcFromToday('da7','<xsl:value-of select='/contact/date-list/date[label="Civil Engineer"]/value1'/>');
                                    </script>
                                    </div></td>
                                </tr>
                                </xsl:if>
                                <xsl:if test='/contact/date-list/date[label="Traffic Signal Specialist"]/value1'>
                                <tr>
                                    <td style="vertical-align: middle;">Traffic Signal Specialist:</td>
                                    <td>               <div id="da8">
                                        <script type="text/javascript"> 
                                            calcFromToday('da8','<xsl:value-of select='/contact/date-list/date[label="Traffic Signal Specialist"]/value1'/>');
                                        </script>
                                    </div></td>
                                </tr>
                                </xsl:if>
                                <xsl:if test='/contact/date-list/date[label="UTC System Specialist"]/value1'>
                                <tr>
                                    <td style="vertical-align: middle;">UTC System Specialist:</td>
                                    <td>               <div id="da9">
                                        <script type="text/javascript"> 
                                            calcFromToday('da9','<xsl:value-of select='/contact/date-list/date[label="UTC System Specialist"]/value1'/>');
                                        </script>
                                    </div></td>
                                </tr>
                                </xsl:if>
                                <tr><td></td><td></td></tr>
                            </table>
                        </center>
                    </div>
                 </td>               
                  <td><span class='field-label'>Years with present Employer</span>
                    <div class='content'>
                        <div id='calculation1' class='entry-value'>
                            <script type="text/javascript"> 
                                calcFromToday('calculation1',"<xsl:value-of select='/contact/date-list/date[label="Start of Employment"]/value1'/>");
                            </script>
                        </div>
                    </div>
                 </td>               
              </tr>
              <tr>
                  <!-- CONTACTS -->
                  <td><span class='field-label'>Contacts</span>
                    <div class='content'>
                        <div class='entry-label'>Phone</div>
                        <div class='entry-value'><xsl:value-of select='/contact/phone-list/phone[label="phone"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Mobile</div>
                        <div class='entry-value'><xsl:value-of select='/contact/phone-list/phone[label="mobile"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Fax</div>
                        <div class='entry-value'><xsl:value-of select='/contact/phone-list/phone[label="fax"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Email</div>
                        <div class='entry-value'><xsl:value-of select='/contact/email-list/email[label="email"]/value'/>&#x00A0;</div>
                        <div class='entry-label'>Website</div>
                        <div class='entry-value'><xsl:value-of select='/contact/www-list/www[label="www"]/value'/>&#x00A0;</div>
                    </div>
                </td>
                <td><span class='field-label'>Qualifications</span>
                    <div class='content'>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Qualification1"]/value'/></div>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Qualification2"]/value'/></div>
                        <div class='entry-value'><xsl:value-of select='/contact/other-list/other[label="Qualification3"]/value'/></div>
                    </div>
                 </td>
              </tr>
            </table>
            
            <!-- EXPERTISE TABLE: This is raw XML stored in the notes text - submitted to contact-submit.ajax.php-->
            <br/>
            <table class='extable'>
                <tr>
                    <th colspan='3' class='field-label'><i>Summarize Experience (Company/Position/Project or Function)</i></th>
                </tr>
              <tr>
                <th>From</th>
                <th>To</th>
                <th></th>
              </tr>
              <xsl:for-each select='/contact/notes/expertise/entry'>
              <tr>
                   <td class='experience-date'><xsl:value-of select='from'/></td>
                   <td class='experience-date'><xsl:value-of select='to'/></td>
                   <td class='experience-text'><xsl:value-of select='text'/></td>
              </tr>
              </xsl:for-each>
            </table>
        <br/>
        <!-- RELATIONS TABLE: Plugin generated XML-->
        <table class='extable'>
            <tr>
                <th colspan='6' class='field-label'><i>Projects in which this candidate is/was involved:</i></th>
            </tr>
            <tr>
                <th>Project Start</th>
                <th>Project Completed</th>
                <th>Value of SWARCO<br/>part</th>
                <th>SWARCO Project Category</th>
                <th>Contract Name</th>
                <th>Position</th>
            </tr>
            <xsl:for-each select='/contact/relationships/relationshipTarget'>
                <tr>
                    <td class=''><xsl:value-of select='awarded/from'/></td>
                    <td class=''><xsl:value-of select='completed/from'/></td>
                    <td class=''>
                        <xsl:attribute name="id">calc<xsl:value-of select="ownerId"/></xsl:attribute>
                        <script type="text/javascript"> 
                            fixNumber('calc'+<xsl:value-of select="ownerId"/>,"<xsl:value-of select='swarcoValue'/>");
                        </script>
                    </td>
                    <td class=''><xsl:value-of select='category'/></td>
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
