<?xml version='1.0' encoding='UTF-8'?>
<xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' version='1.0'> 
    <xsl:output method="html" version='4.0' encoding='UTF-8' indent='yes'/>
    <xsl:template match='/'>
        <html>
            <script type="text/javascript" src="../lib/js/scriptaculous/prototype.js"></script>
            <script type="text/javascript" src="../lib/js/scriptaculous/scriptaculous.js"></script>
            <script type="text/javascript"> <![CDATA[
            function csvList2Checkbox(fieldId,csvList)
            {
                a = csvList.split(',');
                e = document.getElementById(fieldId);
                var out = '';
                
                all = ['Urban Traffic', 'Interurban Traffic', 'Parking', 
                    'Public / Freight Transport', 'Tunnel', 'Infomobility', 'Service / Maintenance'];
                
                for(j=0;j<all.length;j++)
                {
                    checked='';
                    for(i=0;i<a.length;i++)
                        if(a[i]==all[j])
                            checked = 'checked="1"';
                            
                    checked += ' id="cx'+j+'" onClick="calcCategoryResult()"';
                    out = out + '<INPUT TYPE="CHECKBOX" '+checked+' NAME="' + all[j] + '"/>' + all[j] + '<BR/>';
                }   
                e.innerHTML = out;
            }

            function calcCategoryResult()
            {
                i=0;
                checkednames='';
                while(e = document.getElementById('cx'+i))
                {
                    if(e.checked == true)
                    {
                         if(checkednames.length>1)
                             checkednames += ',';
                         checkednames += e.name;
                    }
                    
                    i++;
                }
                
                e = document.getElementById('categoryResult');
                e.setAttribute('value',checkednames);
            }

            function warning(e, yesNo)
            {
                if(yesNo)
                    new Effect.Highlight(e,{ startcolor: '#FF0000', endcolor: '#FFAA00', restorecolor: '#FFAA00' });
                else
                    new Effect.Highlight(e,{ startcolor: '#66FF66', endcolor: '#FFFFFF', restorecolor: '#FFFFFF' });
            }
            
            function checkDateFormat(input)
            {
                a = input.value.split('-');
                if(a[0].length!=4 || a[1].length!=2 || a[2].length!=2 || a[1]>'12' || a[2]>'31')
                {
                    alert('Please enter the date as: YYYY-MM-DD like: 2007-12-31, 1958-07-01.');
                    warning(input,true);
                }
                else
                    warning(input,false);
            }
            
            function isDigit(num)
            {
                var s="1234567890";
                if (s.indexOf(num)!=-1)
                    return true;
                    
                return false;
            }
            
            function checkEURFormat(input)
            {
                v = input.value;
                changed = false;
                out = '';
                for(i=0;i<v.length;i++)
                {
                    if(isDigit(v.charAt(i)))
                        out += v.charAt(i);
                    else
                        changed = true;
                }       
                
                if(changed)
                {
                    alert('Changed money value (punctuation removed). Please verify.');
                    warning(input,false);
                }
                else
                    warning(input,false);
                
                input.value = out;
            }
            
            function toDay()
            {
                d = new Date();
                return d.getFullYear()+'-'+(d.getMonth()+1)+'-'+d.getDate();
            }
            
            function ajaxSubmitForm(sender,statusId)
            {
                document.getElementById(statusId).innerHtml='Sending data ...';
                new Ajax.Updater(statusId, sender.action, { asynchronous:true, parameters:Form.serialize(sender) });
                return false;
            }
            ]]>
            </script>
            
            <style>
                body { text-align: left; font-size: 1em; font-family: Tahoma, sans-serif; font-size: 12px; }
                .table { width: 100%; border: solid 1px #AAA; border-collapse: collapse; }
                .table th { border: solid 1px #AAA; vertical-align: top; padding: 2px; background-color: #EEE; }
                .table td { border: solid 1px #AAA; vertical-align: top; padding: 2px; font-size: 12px; }
                .content { margin-left:0.5cm; }
                
                .address-line { display: block; }
                
                .field-label { font-weight: bold; font-size: 10px; font-family: sans-serif;}
                .entry-label { width: 2cm; font-weight: bold; float: left;}
                .entry-value { display: block; }

                .country-selector { width: 200px; }
                .big-input { width: 250px; }
                
                .experience-date { vertical-align: top; font-weight: bold; border: solid 1px #AAA; }
                .experience-text { border: solid 1px #AAA; }
                .experience-textarea { width: 100%; height:150px; font-family: Verdana, sans-serif; font-size: smaller; }
            </style>
            <body>
<!-- CONTACT -->
                <form method="post">
                    <xsl:attribute name="action">../contact/contact-submit-xsl.php?mode=contact_NoMandatoryEntries&amp;id=<xsl:value-of select='/contact/id'/></xsl:attribute>
                <br/>
                <table class='table'>
                    <tr>
                        <td><span class='field-label'>Project Name</span>
                            <div class='content'>
                                <div class='entry-value'>
                            <input type="text" size='37' name="contact[lastname]">
                                <xsl:attribute name="value"><xsl:value-of select='/contact/name/family'/></xsl:attribute>
                            </input>
                                </div>
                            </div>
                        </td>
                        <td><span class='field-label'>Project Country</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[100][label]' value="Project Country"/>
                                    <input type="hidden" name='other[100][visibility]' value="visible"/>
                                    <input class='big-input' type="text">
                                        <xsl:attribute name="name">other[100][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Project Country"][1]/value'/>
                                        </xsl:attribute>
                                    </input>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class='field-label'>SWARCO company</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[0][label]' value="Applicant"/>
                                    <input type="hidden" name='other[0][visibility]' value="visible"/>
                                    <input class='big-input' type="text">
                                        <xsl:attribute name="name">other[0][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Applicant"][1]/value'/>
                                        </xsl:attribute>
                                    </input>
                                </div>
                            </div>
                        </td>
                        <td><span class='field-label'>SWARCO partner or joint venture</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[1][label]' value="Project Partner"/>
                                    <input type="hidden" name='other[1][visibility]' value="visible"/>
                                    <input class='big-input' type="text">
                                        <xsl:attribute name="name">other[1][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Project Partner"][1]/value'/>
                                        </xsl:attribute>
                                    </input>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class='field-label'>SWARCO Lead Persons</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[101][label]' value="Lead Persons"/>
                                    <input type="hidden" name='other[101][visibility]' value="visible"/>
                                    <input class='big-input' type="text">
                                        <xsl:attribute name="name">other[101][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Lead Persons"][1]/value'/>
                                        </xsl:attribute>
                                    </input>
                                </div>
                            </div>
                        </td>
                        <td><span class='field-label'>Finance</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[102][label]' value="Finance"/>
                                    <input type="hidden" name='other[102][visibility]' value="visible"/>
                                    <input class='big-input' type="text">
                                        <xsl:attribute name="name">other[102][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Finance"][1]/value'/>
                                        </xsl:attribute>
                                    </input>
                                </div>
                            </div>
                        </td>
                    </tr>
<!-- ADDRESS -->
                    <tr>
                        <td><span class='field-label'>Name and Address of Employer</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[2][label]' value="Name of Employer"/>
                                    <input type="hidden" name='other[2][visibility]' value="visible"/>
                                    <input class='big-input' type="text">
                                        <xsl:attribute name="name">other[2][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Name of Employer"][1]/value'/>
                                        </xsl:attribute>
                                    </input>
                                    <xsl:choose>
                                        <xsl:when test="/contact/address-list/address[type='Employer Address']">
                                            <xsl:apply-templates select="/contact/address-list/address[type='Employer Address']">
                                                <xsl:with-param name="index">1</xsl:with-param>
                                            </xsl:apply-templates>
                                        </xsl:when>
                                        <xsl:otherwise>
                                            <xsl:call-template name="addressInput">
                                                <xsl:with-param name="index">1</xsl:with-param>
                                            </xsl:call-template>
                                        </xsl:otherwise>
                                    </xsl:choose>
                                </div>
                            </div>
                        </td>
                        <td><span class='field-label'>Name and Address of Agent</span>
                            <div class='content'>
                                <div class='entry-label'>Name</div>
                                <input type="hidden" name='other[3][label]' value="Superior"/>
                                <input type="hidden" name='other[3][visibility]' value="visible"/>
                                <input type="text" size='20'>
                                    <xsl:attribute name="name">other[3][value]</xsl:attribute>
                                    <xsl:attribute name="value"><xsl:value-of select='/contact/other-list/other[label="Superior"]/value'/></xsl:attribute>
                                </input>
                                <div class='entry-label'>Phone</div>
                                <div class='entry-value'>
                                    <input type="hidden" name='phone[0][label]' value="phone"/>
                                    <input type="hidden" name='phone[0][visibility]' value="visible"/>
                                    <input type="text" size='30'>
                                        <xsl:attribute name="name">phone[0][value]</xsl:attribute>
                                        <xsl:attribute name="value"><xsl:value-of select='/contact/phone-list/phone[label="phone"]/value'/></xsl:attribute>
                                    </input>
                                </div>
                                <div class='entry-label'>Mobile</div>
                                <div class='entry-value'>
                                    <input type="hidden" name='phone[1][label]' value="mobile"/>
                                    <input type="hidden" name='phone[1][visibility]' value="visible"/>
                                    <input type="text" size='30'>
                                        <xsl:attribute name="name">phone[1][value]</xsl:attribute>
                                        <xsl:attribute name="value"><xsl:value-of select='/contact/phone-list/phone[label="mobile"]/value'/></xsl:attribute>
                                    </input>
                                </div>
                                <div class='entry-label'>Fax</div>
                                <div class='entry-value'>
                                    <input type="hidden" name='phone[2][label]' value="fax"/>
                                    <input type="hidden" name='phone[2][visibility]' value="visible"/>
                                    <input type="text" size='30'>
                                        <xsl:attribute name="name">phone[2][value]</xsl:attribute>
                                        <xsl:attribute name="value"><xsl:value-of select='/contact/phone-list/phone[label="fax"]/value'/></xsl:attribute>
                                    </input>
                                </div>
                                <div class='entry-label'>Email</div>
                                <div class='entry-value'>
                                    <input type="hidden" name='email[1][label]' value="email"/>
                                    <input type="hidden" name='email[1][visibility]' value="visible"/>
                                    <input type="text" size='30'>
                                        <xsl:attribute name="name">email[1][value]</xsl:attribute>
                                        <xsl:attribute name="value"><xsl:value-of select='/contact/email-list/email[label="email"]/value'/></xsl:attribute>
                                    </input>
                                </div>
                                <!--div class='entry-label'>Website</div>
                                <div class='entry-value'>
                                    <input type="hidden" name='www[1][label]' value="www"/>
                                    <input type="hidden" name='www[1][visibility]' value="visible"/>
                                    <input type="text" size='30'>
                                        <xsl:attribute name="name">www[1][value]</xsl:attribute>
                                        <xsl:attribute name="value"><xsl:value-of select='/contact/www-list/www[label="www"]/value'/></xsl:attribute>
                                    </input>
                                </div-->
                            </div>
                        </td>               
                    </tr>
<!-- OTHER -->
                    <tr>
                        <td><span class='field-label'>Contract Role</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[4][label]' value="Contract Role"/>
                                    <input type="hidden" name='other[4][visibility]' value="visible"/>
                                    <!--input class='big-input' type="text">
                                        <xsl:attribute name="name">other[4][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Contract Role"][1]/value'/>
                                        </xsl:attribute>
                                    </input-->
                                    <select class="role-selector">
                                        <xsl:attribute name="name">other[4][value]</xsl:attribute>
                                        <option value="Prime Contractor"><xsl:if test="/contact/other-list/other[label='Contract Role'][1]/value='Prime Contractor'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Prime Contractor</option>
                                        <option value="Subcontractor"><xsl:if test="/contact/other-list/other[label='Contract Role'][1]/value='Subcontractor'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Subcontractor</option>
                                        <option value="Managing Contractor"><xsl:if test="/contact/other-list/other[label='Contract Role'][1]/value='Managing Contractor'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Managing Contractor</option>
                                        <option value="Partner in a joint venture"><xsl:if test="/contact/other-list/other[label='Contract Role'][1]/value='Partner in a joint venture'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Partner in a joint venture</option>
                                    </select>
                                </div>
                            </div>
                        </td>               
                        <td><span class='field-label'>SWARCO Project Category</span>
                            <div class='content'>
                                <div class='content'>
                                    <input type="hidden" name='other[5][label]' value="Project Category"/>
                                    <input type="hidden" name='other[5][visibility]' value="visible"/>
                                    <input id='categoryResult' type="hidden" name='other[5][value]' value="">
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Project Category"]/value'/>
                                        </xsl:attribute>
                                    </input>
                                    <div id='calculation2'>
                                    <script type="text/javascript"> 
                                        csvList2Checkbox('calculation2',"<xsl:value-of select='/contact/other-list/other[label="Project Category"]/value'/>");
                                    </script>
                                    </div>
                                </div>                                
                            </div>
                        </td>               
                    </tr>
                    <tr>
                        <td><span class='field-label'>Value of the total contract (EUR)</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[6][label]' value="Total Value"/>
                                    <input type="hidden" name='other[6][visibility]' value="visible"/>
                                    <input type="text" class='big-input' onblur="checkEURFormat(this)">
                                        <xsl:attribute name="name">other[6][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="Total Value"][1]/value'/>
                                        </xsl:attribute><br/>
                                    </input>
                                </div>
                            </div>
                        </td>               
                        <td><span class='field-label'>Value of SWARCO part (EUR)</span>
                            <div class='content'>
                                <div class='entry-value'>
                                    <input type="hidden" name='other[7][label]' value="SWARCO Value"/>
                                    <input type="hidden" name='other[7][visibility]' value="visible"/>
                                    <input type="text" class='big-input' onblur="checkEURFormat(this)">
                                        <xsl:attribute name="name">other[7][value]</xsl:attribute>
                                        <xsl:attribute name="value">
                                            <xsl:value-of select='/contact/other-list/other[label="SWARCO Value"][1]/value'/>
                                        </xsl:attribute><br/>
                                    </input>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class='field-label'>Date of award</span>
                            <div class='content'>
                                <input type="hidden" name='date[0][label]' value="Awarded"/>
                                <input type="hidden" name='date[0][visibility]' value="visible"/>
                                <input type="hidden" name='date[0][value2]'>
                                    <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Awarded"]/value2'/></xsl:attribute>                                                
                                </input>
                                <input type="text" size='20' onblur="checkDateFormat(this)">
                                    <xsl:attribute name="name">date[0][value1]</xsl:attribute>
                                    <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Awarded"]/value1'/></xsl:attribute>
                                </input>
                            </div>
                        </td>               
                        <td><span class='field-label'>Date of completion</span>
                            <div class='content'>
                                <input type="hidden" name='date[1][label]' value="Completed"/>
                                <input type="hidden" name='date[1][visibility]' value="visible"/>
                                <input type="hidden" name='date[1][value2]'>
                                    <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Completed"]/value2'/></xsl:attribute>                                                
                                </input>
                                <input type="text" size='20' onblur="checkDateFormat(this)">
                                    <xsl:attribute name="name">date[1][value1]</xsl:attribute>
                                    <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Completed"]/value1'/></xsl:attribute>
                                </input>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><span class='field-label'>Project Status</span>
                            <div class='content'>
                                <input type="hidden" name='other[8][label]' value="Project Status"/>
                                <input type="hidden" name='other[8][visibility]' value="visible"/>
                                <select class="pj-selector" onchange="document.getElementById('psc-date').value=toDay();">
                                    <xsl:attribute name="name">other[8][value]</xsl:attribute>
                                    <option value="Initial Discussion"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='Initial Discussion'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Initial Discussion</option>
                                    <option value="Prequalification"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='Prequalification'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Prequalification</option>
                                    <option value="Tender"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='Tender'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Tender</option>
                                    <option value="Post Tender discussions"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='Post Tender Discussions'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Post Tender Discussions</option>
                                    <option value="Cancelled"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='Cancelled'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cancelled</option>
                                    <option value="Delayed"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='Delayed'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Delayed</option>
                                    <option value="On Hold"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='On Hold'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>On Hold</option>
                                    <option value="No-bid"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='No-bid'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>No-bid</option>
                                    <option value="Lost"><xsl:if test="/contact/other-list/other[label='Project Status'][1]/value='Lost'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Lost</option>
                                </select>
                                <br/>
                                <input type="hidden" name='other[9][label]' value="Project Status Changed"/>
                                <input type="hidden" name='other[9][visibility]' value="visible"/>
                                <input id='psc-date' type="text" size='20' readonly='1'>
                                    <xsl:attribute name="name">other[9][value]</xsl:attribute>
                                    <xsl:attribute name="value"><xsl:value-of select='/contact/other-list/other[label="Project Status Changed"]/value'/></xsl:attribute>
                                </input>
                            </div>
                        </td>
                        <td><span class='field-label'>Project Opportunity Dates (deadlines or planned dates)</span>
                            <div class='content'>
                                <center>
                                <table style="border-collapse: collapse;">
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            Initial Discussion:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[2][label]' value="Initial Discussion"/>
                                            <input type="hidden" name='date[2][visibility]' value="visible"/>
                                            <input type="hidden" name='date[2][type]' value="once"/>
                                            <input type="hidden" name='date[2][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[2][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Initial Discussion"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            Prequalification:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[3][label]' value="Prequalification"/>
                                            <input type="hidden" name='date[3][visibility]' value="visible"/>
                                            <input type="hidden" name='date[3][type]' value="once"/>
                                            <input type="hidden" name='date[3][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[3][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Prequalification"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            Tender:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[4][label]' value="Tender"/>
                                            <input type="hidden" name='date[4][visibility]' value="visible"/>
                                            <input type="hidden" name='date[4][type]' value="once"/>
                                            <input type="hidden" name='date[4][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[4][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Tender"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            Post Tender Discussions:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[5][label]' value="Post Tender Discussions"/>
                                            <input type="hidden" name='date[5][visibility]' value="visible"/>
                                            <input type="hidden" name='date[5][type]' value="once"/>
                                            <input type="hidden" name='date[5][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[5][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Post Tender Discussions"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            Cancelled:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[6][label]' value="Cancelled"/>
                                            <input type="hidden" name='date[6][visibility]' value="visible"/>
                                            <input type="hidden" name='date[6][type]' value="once"/>
                                            <input type="hidden" name='date[6][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[6][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Cancelled"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            Delayed:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[7][label]' value="Delayed"/>
                                            <input type="hidden" name='date[7][visibility]' value="visible"/>
                                            <input type="hidden" name='date[7][type]' value="once"/>
                                            <input type="hidden" name='date[7][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[7][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Delayed"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            On Hold:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[8][label]' value="On Hold"/>
                                            <input type="hidden" name='date[8][visibility]' value="visible"/>
                                            <input type="hidden" name='date[8][type]' value="once"/>
                                            <input type="hidden" name='date[8][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[8][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="On Hold"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            No-bid:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[9][label]' value="No-bid"/>
                                            <input type="hidden" name='date[9][visibility]' value="visible"/>
                                            <input type="hidden" name='date[9][type]' value="once"/>
                                            <input type="hidden" name='date[9][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[9][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="No-bid"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            Lost:
                                        </td>
                                        <td>
                                            <input type="hidden" name='date[10][label]' value="Lost"/>
                                            <input type="hidden" name='date[10][visibility]' value="visible"/>
                                            <input type="hidden" name='date[10][type]' value="once"/>
                                            <input type="hidden" name='date[10][value2]' value=""/>
                                            <input type="text" size='20' onblur="checkDateFormat(this)">
                                                <xsl:attribute name="name">date[10][value1]</xsl:attribute>
                                                <xsl:attribute name="value"><xsl:value-of select='/contact/date-list/date[label="Lost"]/value1'/></xsl:attribute>
                                            </input>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            Won:
                                        </td>
                                        <td>
                                            <input type="button" name='date[3][label]' value="Won (convert to project)" onclick="alert('Opportunity will be converted to Project when saved!'); document.getElementById('groupSaveInput').name='groups[Project References]'"/>
                                        </td>
                                    </tr>
                                </table>
                                </center>
                            </div>
                        </td>
                    </tr>
                    <!-- SUBMIT AREA 1 -->
                    <tr>
                        <td colspan="3">
                            <input id="groupSaveInput" type="hidden" name='groups[Project Opportunities]' value="1"/>
                            <button type="submit" style="float: right;">Save</button>
                            <input id='dupe' type="hidden" name='duplicateContact' value="0"/>
                            <button type="submit" style="float: right;" onclick="document.getElementById('dupe').value=1;">New</button>
                            <div id='ajaxStatus1' style="display:inline;">Submit contact data when ready ...</div>
                        </td>
                    </tr>
                </table>
                
                <!-- EXPERTISE/NOTES -->
                <!-- EXPERTISE FORM: This is raw XML stored in the notes text - submitted to contact-submit.ajax.php-->
                
                <table class='table'>
                    <tr>
                        <th>Nature of works and special features</th>
                    </tr>
                    <tr>
                        <td class='experience-text'>
                            <textarea class="experience-textarea">
                                <xsl:attribute name="name">contact[notes]</xsl:attribute>
                                <xsl:value-of select='/contact/notes'/>
                            </textarea>
                        </td>
                    </tr>
                </table>
                </form>
            </body>
        </html>
    </xsl:template>
    
    <!-- ADDRESS OUTPUT TEMPLATE -->
    <xsl:template name="addressInput" match="address">
        <xsl:param name="index"/>
        <xsl:if test="dbid">
            <input type="hidden">
                <xsl:attribute name="id">address[<xsl:value-of select="$index" />][refid]</xsl:attribute>
                <xsl:attribute name="name">address[<xsl:value-of select="$index" />][refid]</xsl:attribute>
                <xsl:attribute name="value"><xsl:value-of select='dbid'/></xsl:attribute>
            </input>    
        </xsl:if>
        <input type="hidden" size='30'>
            <xsl:attribute name="name">address[<xsl:value-of select="$index" />][type]</xsl:attribute>
            <xsl:attribute name="value"><xsl:value-of select='type'/></xsl:attribute>
        </input><br/>
        <input type="text" size='30'>
            <xsl:attribute name="name">address[<xsl:value-of select="$index" />][line1]</xsl:attribute>
            <xsl:attribute name="value"><xsl:value-of select='line1'/></xsl:attribute>
        </input><br/>

        <input type="text" size='30'>
            <xsl:attribute name="name">address[<xsl:value-of select="$index" />][line2]</xsl:attribute>
            <xsl:attribute name="value"><xsl:value-of select='line2'/></xsl:attribute>
        </input><br/>
        <input type="text" size='5'>
            <xsl:attribute name="name">address[<xsl:value-of select="$index" />][zip]</xsl:attribute>
            <xsl:attribute name="value"><xsl:value-of select='zip'/></xsl:attribute>
        </input>
        <input type="text" size='25'>
            <xsl:attribute name="name">address[<xsl:value-of select="$index" />][city]</xsl:attribute>
            <xsl:attribute name="value"><xsl:value-of select='city'/></xsl:attribute>
        </input><br/>

        <select class="country-selector">
            <xsl:attribute name="name">address[<xsl:value-of select="$index" />][country]</xsl:attribute>
            <option value="0">(blank)</option>
            <option value="af"><xsl:if test="countrycode='af'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Afghanistan</option>
            <option value="al"><xsl:if test="countrycode='al'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Albania</option>
            <option value="dz"><xsl:if test="countrycode='dz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Algeria</option>
            <option value="as"><xsl:if test="countrycode='as'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>American Samoa</option>
            <option value="ad"><xsl:if test="countrycode='ad'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Andorra</option>
            <option value="ao"><xsl:if test="countrycode='ao'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Angola</option>
            <option value="ai"><xsl:if test="countrycode='ai'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Anguilla</option>
            <option value="aq"><xsl:if test="countrycode='aq'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Antarctica</option>
            <option value="ag"><xsl:if test="countrycode='ag'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Antigua and Barbuda</option>
            <option value="ar"><xsl:if test="countrycode='ar'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Argentina</option>
            <option value="am"><xsl:if test="countrycode='am'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Armenia</option>
            <option value="aw"><xsl:if test="countrycode='aw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Aruba</option>
            <option value="au"><xsl:if test="countrycode='au'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Australia</option>
            <option value="at"><xsl:if test="countrycode='at'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Austria</option>
            <option value="az"><xsl:if test="countrycode='az'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Azerbaijan</option>
            <option value="bs"><xsl:if test="countrycode='bs'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bahamas</option>
            <option value="bh"><xsl:if test="countrycode='bh'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bahrain</option>
            <option value="bd"><xsl:if test="countrycode='bd'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bangladesh</option>
            <option value="bb"><xsl:if test="countrycode='bb'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Barbados</option>
            <option value="by"><xsl:if test="countrycode='by'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Belarus</option>
            <option value="be"><xsl:if test="countrycode='be'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Belgium</option>
            <option value="bz"><xsl:if test="countrycode='bz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Belize</option>
            <option value="bj"><xsl:if test="countrycode='bj'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Benin</option>
            <option value="bm"><xsl:if test="countrycode='bm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bermuda</option>
            <option value="bt"><xsl:if test="countrycode='bt'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bhutan</option>
            <option value="bo"><xsl:if test="countrycode='bo'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bolivia</option>
            <option value="ba"><xsl:if test="countrycode='ba'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bosnia and Herzegovina</option>
            <option value="bw"><xsl:if test="countrycode='bw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Botswana</option>
            <option value="bv"><xsl:if test="countrycode='bv'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bouvet Island (Norway)</option>
            <option value="br"><xsl:if test="countrycode='br'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Brazil</option>
            <option value="io"><xsl:if test="countrycode='io'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>British Indian Ocean Territory</option>
            <option value="bn"><xsl:if test="countrycode='bn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Brunei</option>
            <option value="bg"><xsl:if test="countrycode='bg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Bulgaria</option>
            <option value="bf"><xsl:if test="countrycode='bf'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Burkina Faso</option>
            <option value="bi"><xsl:if test="countrycode='bi'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Burundi</option>
            <option value="kh"><xsl:if test="countrycode='kh'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cambodia</option>
            <option value="cm"><xsl:if test="countrycode='cm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cameroon</option>
            <option value="ca"><xsl:if test="countrycode='ca'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Canada</option>
            <option value="cv"><xsl:if test="countrycode='cv'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cape Verde</option>
            <option value="ky"><xsl:if test="countrycode='ky'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cayman Islands</option>
            <option value="cf"><xsl:if test="countrycode='cf'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Central African Republic</option>
            <option value="td"><xsl:if test="countrycode='td'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Chad</option>
            <option value="cl"><xsl:if test="countrycode='cl'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Chile</option>
            <option value="cn"><xsl:if test="countrycode='cn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>China</option>
            <option value="cx"><xsl:if test="countrycode='cx'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Christmas Island</option>
            <option value="cc"><xsl:if test="countrycode='cc'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cocos (Keeling) Islands</option>
            <option value="co"><xsl:if test="countrycode='co'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Colombia</option>
            <option value="km"><xsl:if test="countrycode='km'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Comoros</option>
            <option value="cg"><xsl:if test="countrycode='cg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Congo</option>
            <option value="ck"><xsl:if test="countrycode='ck'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cook Islands</option>
            <option value="cr"><xsl:if test="countrycode='cr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Costa Rica</option>
            <option value="ci"><xsl:if test="countrycode='ci'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cote D'Ivoire</option>
            <option value="hr"><xsl:if test="countrycode='hr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Croatia</option>
            <option value="cu"><xsl:if test="countrycode='cu'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cuba</option>
            <option value="cy"><xsl:if test="countrycode='cy'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Cyprus</option>
            <option value="cz"><xsl:if test="countrycode='cz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Czech Republic</option>
            <option value="dk"><xsl:if test="countrycode='dk'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Denmark</option>
            <option value="dj"><xsl:if test="countrycode='dj'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Djibouti</option>
            <option value="dm"><xsl:if test="countrycode='dm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Dominica</option>
            <option value="do"><xsl:if test="countrycode='do'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Dominican Republic</option>
            <option value="tl"><xsl:if test="countrycode='tl'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>East Timor</option>
            <option value="ec"><xsl:if test="countrycode='ec'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Ecuador</option>
            <option value="eg"><xsl:if test="countrycode='eg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Egypt</option>
            <option value="sv"><xsl:if test="countrycode='sv'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>El Salvador</option>
            <option value="gq"><xsl:if test="countrycode='gq'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Equatorial Guinea</option>
            <option value="er"><xsl:if test="countrycode='er'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Eritrea</option>
            <option value="ee"><xsl:if test="countrycode='ee'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Estonia</option>
            <option value="et"><xsl:if test="countrycode='et'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Ethiopia</option>
            <option value="fo"><xsl:if test="countrycode='fo'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Faeroe Islands</option>
            <option value="fk"><xsl:if test="countrycode='fk'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Falkland Islands (Malvinas)</option>
            <option value="fj"><xsl:if test="countrycode='fj'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Fiji</option>
            <option value="fi"><xsl:if test="countrycode='fi'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Finland</option>
            <option value="fr"><xsl:if test="countrycode='fr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>France</option>
            <option value="gf"><xsl:if test="countrycode='gf'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>French Guiana</option>
            <option value="pf"><xsl:if test="countrycode='pf'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>French Polynesia</option>
            <option value="ga"><xsl:if test="countrycode='ga'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Gabon</option>
            <option value="gm"><xsl:if test="countrycode='gm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Gambia</option>
            <option value="ge"><xsl:if test="countrycode='ge'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Georgia</option>
            <option value="de"><xsl:if test="countrycode='de'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Germany</option>
            <option value="gh"><xsl:if test="countrycode='gh'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Ghana</option>
            <option value="gi"><xsl:if test="countrycode='gi'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Gibraltar</option>
            <option value="gr"><xsl:if test="countrycode='gr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Greece</option>
            <option value="gl"><xsl:if test="countrycode='gl'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Greenland</option>
            <option value="gd"><xsl:if test="countrycode='gd'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Grenada</option>
            <option value="gp"><xsl:if test="countrycode='gp'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Guadeloupe</option>
            <option value="gu"><xsl:if test="countrycode='gu'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Guam</option>
            <option value="gt"><xsl:if test="countrycode='gt'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Guatemala</option>
            <option value="gn"><xsl:if test="countrycode='gn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Guinea</option>
            <option value="gw"><xsl:if test="countrycode='gw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Guinea-bissau</option>
            <option value="gy"><xsl:if test="countrycode='gy'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Guyana</option>
            <option value="ht"><xsl:if test="countrycode='ht'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Haiti</option>
            <option value="hm"><xsl:if test="countrycode='hm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Heard and Mc Donald Islands</option>
            <option value="hn"><xsl:if test="countrycode='hn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Honduras</option>
            <option value="hk"><xsl:if test="countrycode='hk'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Hong Kong</option>
            <option value="hu"><xsl:if test="countrycode='hu'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Hungary</option>
            <option value="is"><xsl:if test="countrycode='is'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Iceland</option>
            <option value="in"><xsl:if test="countrycode='in'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>India</option>
            <option value="id"><xsl:if test="countrycode='id'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Indonesia</option>
            <option value="ir"><xsl:if test="countrycode='ir'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Iran (Islamic Republic of)</option>
            <option value="iq"><xsl:if test="countrycode='iq'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Iraq</option>
            <option value="ie"><xsl:if test="countrycode='ie'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Ireland</option>
            <option value="il"><xsl:if test="countrycode='il'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Israel</option>
            <option value="it"><xsl:if test="countrycode='it'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Italy</option>
            <option value="jm"><xsl:if test="countrycode='jm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Jamaica</option>
            <option value="jp"><xsl:if test="countrycode='jp'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Japan</option>
            <option value="jo"><xsl:if test="countrycode='jo'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Jordan</option>
            <option value="kz"><xsl:if test="countrycode='kz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Kazakhstan</option>
            <option value="ke"><xsl:if test="countrycode='ke'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Kenya</option>
            <option value="ki"><xsl:if test="countrycode='ki'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Kiribati</option>
            <option value="kp"><xsl:if test="countrycode='kp'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Korea Democratic People's Republic of</option>
            <option value="kw"><xsl:if test="countrycode='kw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Kuwait</option>
            <option value="kg"><xsl:if test="countrycode='kg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Kyrgyzstan</option>
            <option value="la"><xsl:if test="countrycode='la'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Lao People's Democratic Republic</option>
            <option value="lv"><xsl:if test="countrycode='lv'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Latvia</option>
            <option value="lb"><xsl:if test="countrycode='lb'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Lebanon</option>
            <option value="ls"><xsl:if test="countrycode='ls'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Lesotho</option>
            <option value="lr"><xsl:if test="countrycode='lr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Liberia</option>
            <option value="ly"><xsl:if test="countrycode='ly'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Libyan Arab Jamahiriya</option>
            <option value="li"><xsl:if test="countrycode='li'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Liechtenstein</option>
            <option value="lt"><xsl:if test="countrycode='lt'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Lithuania</option>
            <option value="lu"><xsl:if test="countrycode='lu'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Luxembourg</option>
            <option value="mo"><xsl:if test="countrycode='mo'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Macau</option>
            <option value="mk"><xsl:if test="countrycode='mk'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Macedonia The Former Yugoslav Republic of</option>
            <option value="mg"><xsl:if test="countrycode='mg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Madagascar</option>
            <option value="mw"><xsl:if test="countrycode='mw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Malawi</option>
            <option value="my"><xsl:if test="countrycode='my'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Malaysia</option>
            <option value="mv"><xsl:if test="countrycode='mv'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Maldives</option>
            <option value="ml"><xsl:if test="countrycode='ml'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Mali</option>
            <option value="mt"><xsl:if test="countrycode='mt'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Malta</option>
            <option value="mh"><xsl:if test="countrycode='mh'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Marshall Islands</option>
            <option value="mq"><xsl:if test="countrycode='mq'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Martinique</option>
            <option value="mr"><xsl:if test="countrycode='mr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Mauritania</option>
            <option value="mu"><xsl:if test="countrycode='mu'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Mauritius</option>
            <option value="yt"><xsl:if test="countrycode='yt'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Mayotte (France)</option>
            <option value="mx"><xsl:if test="countrycode='mx'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Mexico</option>
            <option value="fm"><xsl:if test="countrycode='fm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Micronesia Federated States of</option>
            <option value="md"><xsl:if test="countrycode='md'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Moldova</option>
            <option value="mc"><xsl:if test="countrycode='mc'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Monaco</option>
            <option value="mn"><xsl:if test="countrycode='mn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Mongolia</option>
            <option value="ms"><xsl:if test="countrycode='ms'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Montserrat</option>
            <option value="ma"><xsl:if test="countrycode='ma'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Morocco</option>
            <option value="mz"><xsl:if test="countrycode='mz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Mozambique</option>
            <option value="mm"><xsl:if test="countrycode='mm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Myanmar</option>
            <option value="na"><xsl:if test="countrycode='na'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Namibia</option>
            <option value="nr"><xsl:if test="countrycode='nr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Nauru</option>
            <option value="np"><xsl:if test="countrycode='np'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Nepal</option>
            <option value="nl"><xsl:if test="countrycode='nl'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Netherlands</option>
            <option value="an"><xsl:if test="countrycode='an'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Netherlands Antilles</option>
            <option value="nc"><xsl:if test="countrycode='nc'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>New Caledonia</option>
            <option value="nz"><xsl:if test="countrycode='nz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>New Zealand</option>
            <option value="ni"><xsl:if test="countrycode='ni'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Nicaragua</option>
            <option value="ne"><xsl:if test="countrycode='ne'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Niger</option>
            <option value="ng"><xsl:if test="countrycode='ng'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Nigeria</option>
            <option value="nu"><xsl:if test="countrycode='nu'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Niue</option>
            <option value="nf"><xsl:if test="countrycode='nf'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Norfolk Island</option>
            <option value="mp"><xsl:if test="countrycode='mp'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Northern Mariana Islands</option>
            <option value="no"><xsl:if test="countrycode='no'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Norway</option>
            <option value="om"><xsl:if test="countrycode='om'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Oman Sultanate Of</option>
            <option value="pk"><xsl:if test="countrycode='pk'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Pakistan</option>
            <option value="pw"><xsl:if test="countrycode='pw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Palau</option>
            <option value="pa"><xsl:if test="countrycode='pa'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Panama</option>
            <option value="pg"><xsl:if test="countrycode='pg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Papua New Guinea</option>
            <option value="py"><xsl:if test="countrycode='py'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Paraguay</option>
            <option value="pe"><xsl:if test="countrycode='pe'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Peru</option>
            <option value="ph"><xsl:if test="countrycode='ph'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Philippines</option>
            <option value="pn"><xsl:if test="countrycode='pn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Pitcairn</option>
            <option value="pl"><xsl:if test="countrycode='pl'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Poland</option>
            <option value="pt"><xsl:if test="countrycode='pt'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Portugal</option>
            <option value="pr"><xsl:if test="countrycode='pr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Puerto Rico</option>
            <option value="qa"><xsl:if test="countrycode='qa'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Qatar</option>
            <option value="re"><xsl:if test="countrycode='re'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Réunion</option>
            <option value="ro"><xsl:if test="countrycode='ro'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Romania</option>
            <option value="ru"><xsl:if test="countrycode='ru'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Russian Federation</option>
            <option value="rw"><xsl:if test="countrycode='rw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Rwanda</option>
            <option value="kn"><xsl:if test="countrycode='kn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Saint Kitts and Nevis</option>
            <option value="lc"><xsl:if test="countrycode='lc'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Saint Lucia</option>
            <option value="vc"><xsl:if test="countrycode='vc'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Saint Vincent and the Grenadines</option>
            <option value="ws"><xsl:if test="countrycode='ws'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Samoa</option>
            <option value="sm"><xsl:if test="countrycode='sm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>San Marino</option>
            <option value="st"><xsl:if test="countrycode='st'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Sao Tome and Principe</option>
            <option value="sa"><xsl:if test="countrycode='sa'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Saudi Arabia</option>
            <option value="sn"><xsl:if test="countrycode='sn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Senegal</option>
            <option value="sc"><xsl:if test="countrycode='sc'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Seychelles</option>
            <option value="sl"><xsl:if test="countrycode='sl'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Sierra Leone</option>
            <option value="sg"><xsl:if test="countrycode='sg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Singapore</option>
            <option value="sk"><xsl:if test="countrycode='sk'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Slovakia (Slovak Republic)</option>
            <option value="si"><xsl:if test="countrycode='si'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Slovenia</option>
            <option value="sb"><xsl:if test="countrycode='sb'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Solomon Islands</option>
            <option value="so"><xsl:if test="countrycode='so'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Somalia</option>
            <option value="za"><xsl:if test="countrycode='za'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>South Africa</option>
            <option value="gs"><xsl:if test="countrycode='gs'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>South Georgia and the South Sandwich Islands</option>
            <option value="kr"><xsl:if test="countrycode='kr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>South Korea</option>
            <option value="es"><xsl:if test="countrycode='es'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Spain</option>
            <option value="lk"><xsl:if test="countrycode='lk'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Sri Lanka</option>
            <option value="sh"><xsl:if test="countrycode='sh'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>St. Helena</option>
            <option value="pm"><xsl:if test="countrycode='pm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>St. Pierre and Miquelon</option>
            <option value="sd"><xsl:if test="countrycode='sd'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Sudan</option>
            <option value="sr"><xsl:if test="countrycode='sr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Suriname</option>
            <option value="sj"><xsl:if test="countrycode='sj'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Svalbard and Jan Mayen Islands (Norway)</option>
            <option value="sz"><xsl:if test="countrycode='sz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Swaziland</option>
            <option value="se"><xsl:if test="countrycode='se'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Sweden</option>
            <option value="ch"><xsl:if test="countrycode='ch'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Switzerland</option>
            <option value="sy"><xsl:if test="countrycode='sy'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Syrian Arab Republic</option>
            <option value="tw"><xsl:if test="countrycode='tw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Taiwan</option>
            <option value="tj"><xsl:if test="countrycode='tj'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Tajikistan</option>
            <option value="tz"><xsl:if test="countrycode='tz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Tanzania United Republic of</option>
            <option value="th"><xsl:if test="countrycode='th'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Thailand</option>
            <option value="tg"><xsl:if test="countrycode='tg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Togo</option>
            <option value="tk"><xsl:if test="countrycode='tk'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Tokelau</option>
            <option value="to"><xsl:if test="countrycode='to'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Tonga</option>
            <option value="tt"><xsl:if test="countrycode='tt'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Trinidad and Tobago</option>
            <option value="tn"><xsl:if test="countrycode='tn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Tunisia</option>
            <option value="tr"><xsl:if test="countrycode='tr'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Turkey</option>
            <option value="tm"><xsl:if test="countrycode='tm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Turkmenistan</option>
            <option value="tc"><xsl:if test="countrycode='tc'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Turks and Caicos Islands</option>
            <option value="tv"><xsl:if test="countrycode='tv'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Tuvalu</option>
            <option value="ug"><xsl:if test="countrycode='ug'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Uganda</option>
            <option value="ua"><xsl:if test="countrycode='ua'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Ukraine</option>
            <option value="ae"><xsl:if test="countrycode='ae'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>United Arab Emirates</option>
            <option value="gb"><xsl:if test="countrycode='gb'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>United Kingdom</option>
            <option value="us"><xsl:if test="countrycode='us'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>United States</option>
            <option value="um"><xsl:if test="countrycode='um'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>United States Minor Outlying Islands</option>
            <option value="uy"><xsl:if test="countrycode='uy'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Uruguay</option>
            <option value="uz"><xsl:if test="countrycode='uz'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Uzbekistan</option>
            <option value="vu"><xsl:if test="countrycode='vu'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Vanuatu</option>
            <option value="va"><xsl:if test="countrycode='va'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Vatican City State (Holy See)</option>
            <option value="ve"><xsl:if test="countrycode='ve'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Venezuela</option>
            <option value="vn"><xsl:if test="countrycode='vn'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Vietnam</option>
            <option value="vg"><xsl:if test="countrycode='vg'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Virgin Islands (British)</option>
            <option value="vi"><xsl:if test="countrycode='vi'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Virgin Islands (U.S.)</option>
            <option value="wf"><xsl:if test="countrycode='wf'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Wallis and Futuna Islands</option>
            <option value="eh"><xsl:if test="countrycode='eh'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Western Sahara</option>
            <option value="ye"><xsl:if test="countrycode='ye'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Yemen</option>
            <option value="yu"><xsl:if test="countrycode='yu'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Yugoslavia</option>
            <option value="zm"><xsl:if test="countrycode='zm'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Zambia</option>
            <option value="zw"><xsl:if test="countrycode='zw'"><xsl:attribute name="selected">yes</xsl:attribute></xsl:if>Zimbabwe</option>
        </select>
    </xsl:template>
    
</xsl:stylesheet>
