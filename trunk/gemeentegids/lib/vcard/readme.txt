VCARD Class:

Class to manipulate with vCard information, according vCard v2.1 and vCard v3.0.
Class is API to work with vCard information and completely free. You may modify, copy
and use it however you need and wherever you need.
IMPORTANT: You able to try complete application used this class. This application 
is for your reference ONLY! Do not copy this application for your production websites or
commercial use. This is copyright application! You able to run this application to look 
at usage of vCard class. You may also borrow PHP code from this application for your own one. 
Contact me if you have any questions.

HOW TO:

1. Copy all files to any directory available under your web server.
2. Run index.phtml file
3. You will be able to play with application:
	upload existing vCard
	enter new vCard
	modify any information on vCard
	download/preview vCard entered
	save it to a file (it will try to save it to /tmp/ directory)
NOTE: option 1 to 3 for demonstration purpose ONLY!
4. Use vCard class to build your own vCard application or contact me.

REFERENCES:

1. http://www.imc.org/pdi/ - the Internet Mail Consortium 
2. http://www.imc.org/pdi/vcardoverview.html - vCard Overview
3. http://www.imc.org/pdi/vcard-21.txt - vCard version 2.1 Specification 
4. http://www.ietf.org/rfc/rfc2425.txt - vCard v.3.0 Specification (MIME Content-Type for Directory Information)
5. http://www.ietf.org/rfc/rfc2426.txt - vCard v.3.0 Specification (vCard MIME Directory Profile)

OPTIONS AND FEATURES:

1. In constructor of the class you have just one parameter with default value.
	VCARD($version = "2.1")
	$version - support "2.1" and "3.0".

	There are few properties:
	$types - types array of vCard object
	$lasterror_msg - last error message
	$lasterror_num - unique last error number 
	$mailer - MAILER property 

2. General methods of the class:
	$vcard->resetvCard(); - begin new vCard
	$vcard->setvCard($input); - set vCard object by vCard formatted string ($input)
	$vcard->getvCard($version); - get vCard formatted string according version number ($version)
	$vcard->downloadvCard($version); - send vCard formatted string according version number to browser (download)
	$vcard->findTVP($input, $TVPattr = "T", $condition = "AND"); - get entries contain value(s) 
		specified in $input, return array entries.
		$param: "T"-type, "P"-parameter(s), "V"-value, "TP"- type and parameter(s)
		$input - may be string or array of mix type and parameters
		$condition: "AND", "OR" - to specify boolean logic for array $input
	
3. IDENTIFICATION TYPES methods:
	setName($lastName = "", $firstName = "", $middleNames = "", $prefixes = "", $suffixes = "") - 
		set N type - To specify the components of the name of the object the vCard represents.
	getName($attr = "LAST") - get N type; $attr: "LAST", "FIRST", "MIDDLE", "PREF", "SUFF"
	setFormattedName($formattedName = "") - set FN type - To specify the formatted text 
		corresponding to the name of the object the vCard represents.
	getFormattedName() - get FN type.
	setNickName($nickName = "") - set NICKNAME type - To specify the text corresponding to the 
		nickname of the object the vCard represents.
	getNickName() - get NICKNAME type.
	setBirthDate($BirthYear = "", $BirthMonth = "", $BirthDay = "") - set BDAY type - To specify the 
		birth date of the object the vCard represents. internal date representation in ISO8601 format, ex. 2002-03-22
	getBirthDate($attr = "BDATE") - get BDAY type; $attr: "DAY", "MONTH", "YEAR", "BDATE"

4. DELIVERY ADDRESSING TYPES methods:
	setAdr($pobox = "", $extended = "", $street = "", $city = "", $province = "", $postal = "", $country = "", $attr = "") -
		set ADR type - To specify the components of the delivery address for the vCard object.
		$attr: (string or array) "DOM", "INTL", "HOME", "WORK", "POSTAL", "PARCEL"
	getAdr($component = "COUNTRY", $attr = "", $condition = "") - return array of address components, 
		meet your criteria if $condition: "AND"; return single address component if $condition: "";
		$component: "POBOX", "EXTENDED", "STREET", "CITY", "PROVINCE", "POSTAL", "COUNTRY"
	setLabel($pobox = "", $extended = "", $street = "", $city = "", $province = "", $postal = "", $country = "", $attr = "") - 
		set LABEL type - To specify the formatted text corresponding to delivery address of the object the vCard represents.
		$attr: (string or array) "DOM", "INTL", "HOME", "WORK", "POSTAL", "PARCEL"
	getLabel($attr = "", $condition = "") - return array of address labels, meet your criteria 
		if $condition: "AND", "OR"; return single address label if $condition: ""

5. TELECOMMUNICATIONS ADDRESSING TYPES methods:
	setTel($tel = "", $attr = "") - set TEL type - To specify the telephone number for telephony
		communication with the object the vCard represents.
		$attr: (string or array) "PREF", "WORK", "HOME", "VOICE", "FAX", "MSG", "CELL", "PAGER", "BBS", "CAR", "MODEM", "ISDN", "VIDEO", "PCS"
	getTel($attr = "", $condition = "") - return array of phone numbers, meet your criteria if $condition: "AND", "OR";
		return single phone number if $condition: "";
	setEmail($email = "", $attr = "INTERNET") - set EMAIL type - To specify the electronic mail address for
		communication with the object the vCard represents. $attr: (string or array) "INTERNET", "X400", "PREF"(default e-mail), ...
	getEmail($attr = "INTERNET", $condition = "") - return array of e-mails, meet your criteria if $condition: "AND", "OR";
		return single e-mail if $condition: "";
	setMailer($new_mailer) - set MAILER type - To specify the type of electronic mail software 
		that is used by the individual associated with the vCard.
	getMailer() - get MAILER type
		
6. GEOGRAPHICAL TYPES methods:
	setTZ($tz = "") - set TZ type - To specify information related to the time zone of the object the vCard represents.
	getTZ() - get TZ type
	setGeo($geo = "") - set GEO type - To specify information related to the global positioning of the object the vCard represents.
	getGeo() - get GEO type

7. ORGANIZATIONAL TYPES methods:
	setTitle($title = "") - set TITLE type - To specify the job title, functional position or
		function of the object the vCard represents.
	getTitle() - get TITLE type
	setRole($role = "") - set ROLE type - To specify information concerning the role, occupation,
		or business category of the object the vCard represents.
	getRole() - get ROLE type
	setOrg($orgName = "", $orgUnit = "", $orgUnits = "") - set ORG type - To specify the organizational 
		name and units associated with the vCard.
	getOrg($attr = "ORGNAME") - get ORG type; $attr: "ORGNAME", "ORGUNIT", "ORGUNITS", "ALL"

8. EXPLANATORY TYPES methods:
	setCategories($categories = "") - set CATEGORIES type - To specify application category 
		information about the vCard.
	getCategories() - get CATEGORIES type
	setNote($note = "") - set NOTE type - To specify supplemental information or a comment that
		is associated with the vCard.
	getNote() - get NOTE type
	setProdID($prodID = "") - set PRODID type - To specify the identifier for the product 
		that created the vCard object.
	getProdID() - get PRODID type.
	setRevision() - set REV type - To specify revision information about the current vCard.
	getRevision() - get REV type
	setSortString($sortString = "")- set SORT-STRING type - To specify the family name or given 
		name text to be used for national-language-specific sorting of the FN and N types.
	getSortString() - get SORT-STRING type
	setUID($UID = "") - set UID type - To specify a value that represents a globally unique 
		identifier corresponding to the individual or resource associated with the vCard. 
	getUID() - get UID type 
	setUrl($url = "", $attr = "") - set URL type - To specify a uniform resource locator 
		associated with the object that the vCard refers to.
		$attr: "", "WORK", "HOME" (assume "" is "WORK" url)
	getUrl($attr = "") - get URL type
	setVersion($new_version) - set VERSION type - To specify the version of the vCard specification
		used to format this vCard.
	getVersion() - get VERSION type
	
9. SECURITY TYPES methods:
	setClass($class = "") - set CLASS type - To specify the access classification for a vCard object.
		ex. $class: "PUBLIC", "PRIVATE", "CONFIDENTIAL"
	getClass() - get CLASS type
	setKey($key = "", $attr = "X509") - set KEY type - To specify a public key or authentication 
		certificate associated with the object that the vCard represents.
		$attr: "X509", "PGP"
	getKey($attr = "X509") - get KEY type
	
10. EXTENDED TYPES methods:
	setXXX($value = "", $attr = "") - set X- type - To specify non-standard information related 
		to the object the vCard represents.
	getXXX($attr = "") - get X- type 
	
11. BINARY TYPES methods:
	setBinary($type = "PHOTO", $value = "", $attr = "JPEG") - set PHOTO, LOGO or SOUND types - see RFC for description
		$type: "PHOTO", "LOGO", "SOUND"
		if $value: base64(bynary data) - $attr: "GIF", "WMF", "BMP", "TIFF", "PDF", "JPEG", "MPEG", "AVI", "WAVE", "PCM", "AIFF" ...
		if $value: URL - $attr: "URL"
	getBinary($type = "PHOTO", $attr = "") - get PHOTO, LOGO or SOUND types

12. Private and helpful methods:
	decode($input) - decode any string according version number
	encode($input) - encode any string according version number
	quoted_printable_encode($input = "", $line_max = 76) - function source from - 
		http://www.php.net/manual/en/function.quoted-printable-decode.php
	_getTVP($input, $TVPattr = "T") - get value of Type, Parameter(s) or it's Value from vCard line
		$param: "T"-type, "P"-parameter(s), "V"-value, "TP"- type and parameter(s)
	_setInternalType($input = "") - private function to set internal key representation of $types(), 
		such as $type|$param1|$param2...; $input - may be $type or array of mix $type and parameters
		Note: first element of input array MUST be $type
	_getInternalType($input = "", $attr = "T") - private function to get $type or $parameters by 
		internal key representation $input - string internal key representation, $attr - what retrive: 
		"T"-type, "P"-parameter(s), "TP"- type and parameter(s)
		Note: several parameters will be separated with ";"
	
	
	

Author: Viatcheslav Ivanov, E-Witness Inc., Canada;
mail: ivanov@e-witness.ca;
web: www.e-witness.ca; www.coolwater.ca; www.strongpost.net;
version: 1.00 /09.20.2002
