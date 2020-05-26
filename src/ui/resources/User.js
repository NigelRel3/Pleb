class User	{
	constructor( div, paneName )	{
		this.jwtToken = null;
		this.data = null;
		this.workspace = null;
	}
	
	setWorkspace ( workspace)	{
		this.workspace = workspace;
	}
	
	login() {
		document.getElementById("loginMessage").textContent ="";
		document.getElementById("submitLogin").onclick = function(){
			var xmlhttp = new XMLHttpRequest();

			xmlhttp.onreadystatechange = function() {
				if (xmlhttp.readyState == XMLHttpRequest.DONE) {
			    	if (xmlhttp.status == 200) {
			    		document.getElementById("login").style.display = "none";
			    		document.getElementById("logout").style.display = "block";
			    		this.jwtToken = JSON.parse(xmlhttp.response).token
			    		this.data = this.parseJwt(this.jwtToken);
					    modal.style.display = "none";
					    this.workspace.loadLayout();
			      	}
				  	else if (xmlhttp.status == 401) {
				  		document.getElementById("loginMessage").textContent 
				  			= "Login not recognised";
				    }
				}
		    }.bind(this);

		    const userName = document.getElementById("userName").value;
		    const password = document.getElementById("password").value;
		    xmlhttp.open("POST", "/login");
		    xmlhttp.setRequestHeader("Content-Type", "application/json");
		    xmlhttp.send(JSON.stringify({username:userName, password:password}));
		}.bind(this)
		document.getElementById("cancelLogin").onclick = function(){
		    modal.style.display = "none";
		}

		let modal = document.querySelector(".modal")
		window.onclick = function(e){
			if(e.target == modal){
				modal.style.display = "none"
			}
		}
		modal.style.display = "block";
		// TODO focus and enter for submit
		document.getElementById("userName").focus();
	}

	logout() {
		this.jwtToken = null;
		document.getElementById("logout").style.display = "none";
		document.getElementById("login").style.display = "block";
		document.getElementById("workspace").innerHTML = "";
		this.workspace.reset(); 
	}
	
	fetchUserData(infoType, callback) {
		if ( this.jwtToken === null )	{
			return;
		}
		var xmlhttp = new XMLHttpRequest();

		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == XMLHttpRequest.DONE) {
		    	if (xmlhttp.status == 200) {
	    			callback(JSON.parse(xmlhttp.response));
		      	}
			  	else if (xmlhttp.status == 401) {
			  		console.log("Error="+xmlhttp.response);
			    }
			  	else if (xmlhttp.status == 404) {
			  		console.log("Error="+xmlhttp.response);
			    }
			}
	    };
	    
	    xmlhttp.open("GET", "/userdata/"+infoType, true);
	    xmlhttp.setRequestHeader('Authorization', 'Bearer ' + this.jwtToken);
	    xmlhttp.send();
	}

	setUserData( infoType, infoData ) {
		if ( this.jwtToken === null )	{
			return;
		}
		var xmlhttp = new XMLHttpRequest();
		
	    console.log(infoData);
	    
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == XMLHttpRequest.DONE) {
		    	if (xmlhttp.status == 200) {
		    		// console.log(xmlhttp.response);
		      	}
			  	else if (xmlhttp.status == 401) {
			  		console.log("Error="+xmlhttp.response);
			    }
			  	else if (xmlhttp.status == 404) {
			  		console.log("Error="+xmlhttp.response);
			    }
			}
	    };
	    
	    xmlhttp.open("POST", "/userdata/" + infoType, true);
	    xmlhttp.setRequestHeader('Authorization', 'Bearer ' + this.jwtToken);
	    xmlhttp.setRequestHeader("Content-Type", "application/json");
	    xmlhttp.send( JSON.stringify( infoData ) );
	}
	
	parseJwt (token) {
	    var base64Url = token.split('.')[1];
	    var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
	    var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
	        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
	    }).join(''));

	    return JSON.parse(jsonPayload);
	};

}