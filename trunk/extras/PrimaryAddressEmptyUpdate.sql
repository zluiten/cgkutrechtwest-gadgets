UPDATE a_contact, a_address SET primaryAddress=refid
WHERE `primaryAddress` = ''
AND a_contact.id = a_address.id
