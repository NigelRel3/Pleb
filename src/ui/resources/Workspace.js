class Workspace {
	constructor( basePane, user )	{
		this.classes = {};
		this.user = user;
		this.basePane = basePane;
		this.currentWorkPanel = null;
		this.reset();
	}
	
	reset()	{
		this.paneList = { workspace : { element : this.basePane, 
			classToUse : null,
			clearfix: null }};
		this.layout = null;
		this.consolePane = null;
	}
	
	isHorizontal ( orientation )	{
		return (orientation == "left" || orientation == "right" );
	}
	
	addPane ( parentPane, name, layout ) {
		let orientation = layout.orientation;
		let size = layout.size;
		// check parent pane
		let parent = this.paneList[parentPane];
		if ( parent == null )	{
			return false;
		}
		let div = null;
		if ( this.paneList[parentPane].clearfix == null &&
				this.isHorizontal(orientation)) {
			div = document.createElement('div');
			div.classList.toggle('clearfix');
			this.paneList[parentPane].clearfix = 
				this.paneList[parentPane].element.appendChild(div);

		}
		div = document.createElement('div');
		div.id = "workspace_" + name;
		div.style.setProperty('float',orientation,'');
		div.style.setProperty('border-style','ridge','');
		if ( orientation == "left" || orientation == "bottom" ) {
			this.addResize(this.paneList[parentPane].element, 
					div, orientation);
		}

		let newElement = null;
		if ( this.isHorizontal(orientation) ) {
			let element = this.paneList[parentPane].clearfix;
			newElement = element.parentElement.insertBefore(div, element);
		}
		else	{
			newElement = this.paneList[parentPane].element.appendChild(div);
		}
		this.paneList[name] = { element : newElement, 
				classToUse : null,
				clearfix : null};
		if (size != null )	{
			if ( this.isHorizontal(orientation) ) {
				div.style.setProperty('width',size,'');
				div.style.setProperty('height','100%','');
			}
			else	{
				div.style.setProperty('height',size,'');
				div.style.setProperty('width','100%','');
			}
		}
		
		return div;
	}
	
	addResize ( parent, div, orientation )	{
		let size = document.createElement('span');
		size.classList.add("barsize");
		size.classList.add("glyphicon");
		if ( this.isHorizontal(orientation) ) {
			size.classList.add("glyphicon-resize-horizontal");
		}
		else	{
			size.classList.add("glyphicon-resize-vertical");
		}
		div.appendChild(size);
		size.addEventListener('mousedown', 
			function ( div, e ) { 
				return this.resizePane (parent, e);
			}
			.bind(this, parent, orientation)
		);
	}
	
	resizePane ( parent, orientation, e ) { 
	    e = e || window.event;
	    e.preventDefault();
	    document.onmouseup = 
		    function ( e ) { 
	    		document.onmouseup = null;
	    		document.onmousemove = null;
	    		this.saveLayout();
	    	}.bind (this);
	    document.onmousemove = 
		    function ( div, orientation, e ) { 
			    e = e || window.event;
			    e.preventDefault();
			    let panes = parent.childNodes;
			    if ( this.isHorizontal(orientation) ) {
				    let width = e.clientX / window.innerWidth * 100;
				    panes[0].style.width = width + "%";
				    panes[1].style.width = (100-width) + "%";
			    }
			    else {
				    let height = e.clientY / window.innerHeight * 100;
				    panes[0].style.height = height + "%";
				    panes[1].style.height = (100-height) + "%";
			    }
		    }
			.bind(this, parent, orientation );
    }

	loadLayout ()	{
		this.user.fetchUserData( "ScreenLayout", this.setLayout.bind(this) );
	}
	
	setLayout ( layout )	{
		this.layout = layout;
		this.layoutPanes(layout.panes, "workspace");
	}
	
	layoutPanes ( segment, parent )	{
		for ( const pane in segment )	{
			let div = this.addPane(parent, pane, segment[pane]);
			if ( segment[pane].classToUse != null ){
				if ( this.classes[segment[pane].classToUse] != null ){
					this.paneList[pane].classToUse = 
						new this.classes[segment[pane].classToUse](this,
								div, pane);
				}
				else {
					console.log("Class not found for " +segment[pane].classToUse);
				}
			}
			if ( segment[pane].panes != null )	{
				this.layoutPanes(segment[pane].panes, pane);
			}
		}
		
	}
	
	saveLayout()	{
		this.updateLayout(this.layout.panes);
		
		this.user.setUserData("screenLayout", this.layout);
	}
	
	updateLayout ( panes )	{
		for ( const pane in panes )	{
			if ( this.isHorizontal(panes[pane].orientation))	{
				panes[pane].size = this.paneList[pane].element.style.width;
			}
			else	{
				panes[pane].size = this.paneList[pane].element.style.height;
			}
			if ( panes[pane].panes != null )	{
				this.updateLayout(panes[pane].panes);
			}
		}		
	}
	
	registerClass ( className, classToRegsiter )	{
		this.classes[className] = classToRegsiter;
	}
	
	registerConsole ( pane )	{
		this.consolePane = pane;
	}
	
	setCurrentWorkPanel ( pane )	{
		this.currentWorkPanel = pane;
	}

	select (tabName, classToLoad )	{
		this.currentWorkPanel.add (tabName, classToLoad);
	}
	
	logMessage ( message )	{
		if ( this.consolePane != null )	{
			this.consolePane.displayMessage ( message );
		}
	}
	
	getUser ()	{
		return this.user;
	}
}




