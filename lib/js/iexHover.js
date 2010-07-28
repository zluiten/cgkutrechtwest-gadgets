  function sucheUL(UL) {
    do {
      if(UL) UL = UL.nextSibling;
      if(UL && UL.nodeName == "UL") return UL;
    }
    while(UL);
    return false;
  }

  function einblenden(obj) {
    if (false == navigator.userAgent.search("MSIE")>0)
      return;
    var UL = sucheUL(obj.firstChild);
    if (!UL)
        return;
    UL.style.visibility = "visible";
  }
  
  function ausblenden(obj) {
    if (false == navigator.userAgent.search("MSIE")>0)
      return;
    var UL = sucheUL(obj.firstChild);
    if (!UL)
        return;
    UL.style.visibility = "hidden";
  }