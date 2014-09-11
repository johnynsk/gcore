var tmp=null;
window.onload=function()
{
	var a=document.getElementsByClassName("toggler");
	for(i in a)
	{
		a[i].onclick=function(c){
			c=c.toElement;
			var active=c.attributes.getNamedItem("data-toggle").value;
			var a=document.getElementsByClassName("toggler");
			for(var i in a)
			{
				if(typeof(a[i])!="object")
					continue;
				a[i].classList.remove("active");
			}
			c.classList.add("active");
			var a=document.getElementsByClassName("toggler-block");
			for(var i in a)
			{
				if(typeof(a[i])!="object")
					continue;
				if(a[i].attributes.getNamedItem("data-toggle").value==active)
					a[i].classList.remove("hidden");
				else
					a[i].classList.add("hidden");
			}
		}
	}
	var a=document.getElementsByClassName("form_input");
	for(i in a)
	{
		a[i].onchange=function(c){
			c=c.srcElement;
			var n=c.parentNode.parentNode.getElementsByClassName("form_null")[0];
			if(c.value.length>0&&n.checked)
				n.click();
			if(c.type=="checkbox")
			{
				var t=c.parentNode.getElementsByClassName("sysval")[0];
				var i=c.parentNode.getElementsByClassName("form_checkfake")[0];
				t.innerHTML="false";
				i.value="false";
				if(c.checked)
				{
					t.innerHTML="true";
					i.value="true";
				}
			}
		};
	}
	var a=document.getElementsByClassName("form_null");
	for(i in a)
	{
		a[i].onclick=function(c)
		{
			c=c.toElement;
			var r=c.parentNode.parentNode;
			var i=r.getElementsByClassName("form_input")[0];
			tmp=i;
			if(!c.checked)
			{
				if(!r.classList.contains("using"))
					r.classList.add("using");
				if(i.type!="checkbox")
				{
					var attr=document.createAttribute("name");
					attr.value=i.attributes.getNamedItem("data-name").value;
					i.attributes.setNamedItem(attr);
				}else{
					var i=r.getElementsByClassName("form_checkfake")[0];
					var attr=document.createAttribute("name");
					attr.value=i.attributes.getNamedItem("data-name").value;
					i.attributes.setNamedItem(attr);
				}
			}else{
				if(r.classList.contains("using"))
					r.classList.remove("using");
				if(i.type!="checkbox")
					i.attributes.removeNamedItem("name");
				else{
					var i=r.getElementsByClassName("form_checkfake")[0];
					i.attributes.removeNamedItem("name");
				}
			}
		}
	}
}
