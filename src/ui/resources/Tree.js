class Tree {
	constructor( name, structure, callback )	{
		this.name = name;
		this.structure = structure;
		this.addCallback = null;
		this.clickCallback = null;
		this.deleteCallback = null;
		this.updateCallback = null;
		this.addIcon = null;
		this.deleteIcon = null;
	}
	
	setAddCallback ( callback )	{
		this.addCallback = callback;
	}
	setClickCallback ( callback )	{
		this.clickCallback = callback;
	}
	setDeleteCallback ( callback )	{
		this.deleteCallback = callback;
	}
	setUpdateCallback ( callback )	{
		this.updateCallback = callback;
	}
	
	display ( elementToDisplayIn )	{
		let base = this.structure.menu;
		let basDiv = document.createElement("div");
		
		this.addIcon = this.structure.AddIcon;
		this.deleteIcon = this.structure.DeleteIcon;
		
		basDiv.classList.add("tree-list");
		let baseElement = document.createElement("ul");
		baseElement.setAttribute("id", "list_"+base);
		elementToDisplayIn.appendChild(basDiv);
		basDiv.appendChild(baseElement);
		this.buildDisplay(baseElement, this.structure[base], base, base);
	}
	
	buildDisplay ( parentElement, node, label, pathToNode )	{
		node.pathToNode = pathToNode;
		node.labelLI = document.createElement("li")
		node.iconElement = null;
		if ( node.icon != null )	{
			node.iconElement = document.createElement("span");
			node.iconElement.className = "list-icon "+node.icon;
			node.labelLI.appendChild(node.iconElement);
		}
		node.labelText = document.createElement("input");
		node.labelText.type = "text";
		node.labelText.setAttribute("readonly", true);
		node.labelText.value = node.title;
		node.labelText.style.border = 0;
		node.labelText.style.background = "transparent";
		node.labelLI.appendChild(node.labelText);
		this.buildAddOptions(label, node);
		parentElement.appendChild(node.labelLI);
		node.sub = document.createElement("ul");
		node.sub.classList.add("nested");
		node.labelLI.appendChild(node.sub);
		if ( node.icon != null )	{
			node.iconElement.onclick = this.toggleOpen.bind (this, node);
		}
		if ( node.nodes != null )	{
			if ( node.options.open == true )	{
				node.sub.classList.add("active");
			}
			for ( const subMenu in node.nodes )	{
				this.buildDisplay(node.sub, node.nodes[subMenu], 
						subMenu, pathToNode + "." + subMenu);
			}
		}
	}
	
	toggleOpen ( node )	{
		node.sub.classList.toggle("active");
		node.options.open = !node.options.open;
		this.updateCallback(this.structure);
	}
	
	editLabel ( node )	{
		node.labelText.removeAttribute("readonly");
		node.labelText.style.border = "2px solid";
		node.labelText.classList.remove('background');
		// Set focus to field
		node.labelText.focus();
		node.labelText.onblur = function (node)	{
			
			// TODO check if already exists in level
			
			node.labelText.setAttribute("readonly", true);
			node.labelText.style.border = 0;
			node.labelText.style.background = "transparent";
			node.title = node.labelText.value;
			//console.log(JSON.stringify(this.structure));
			this.updateCallback(this.structure);
		}.bind(this, node)
	}
	
	buildAddOptions (label,  node )	{
		if ( node.options.clickable == true )	{
			node.labelText.classList.add("clickable");
			node.labelText.onclick = function() {
				this.clickCallback(label, node);
			}.bind(this);
		}
		if ( node.options.editable == true )	{
			node.editIcon = document.createElement("span");
			node.editIcon.className = "list-adjust glyphicon glyphicon-pencil clickable";
			node.labelLI.appendChild(node.editIcon);
			node.editIcon.onclick = function() {
				this.editLabel(node);
			}.bind(this);
		}
		if ( node.options.add == true )	{
			node.addIcon = document.createElement("span");
			node.addIcon.className = "list-adjust "+this.addIcon;
			node.labelLI.appendChild(node.addIcon);
			node.addIcon.onclick = function() {
				this.addClicked(node);
			}.bind(this);
		}
		if ( node.options.deletable == true )	{
			node.deleteIcon = document.createElement("span");
			node.deleteIcon.className = "list-adjust "+this.deleteIcon;
			node.labelLI.appendChild(node.deleteIcon);
			node.deleteIcon.onclick = function() {
				this.deleteClicked(label, node);
			}.bind(this);
		}
	}
	
	addClicked ( node )	{
		let newNodeName = node.addOptions.base + node.addOptions.id;
		node.addOptions.id++;
		
		let newNode = {
				title: "New",
				options: {
					clickable: true,
					deletable: true,
					editable: true
				},
				classToUse: node.addOptions.classToUse
		};
		if ( node.nodes == null || Array.isArray(node.nodes) )	{
			node.nodes = { [newNodeName]: newNode};
		}
		else	{
			node.nodes[newNodeName] = newNode;
		}
		
		// Add into structure
		this.buildDisplay(node.sub, node.nodes[newNodeName], newNodeName,
				node.pathToNode + "." + newNodeName);
		// Open parent menu item
		node.options.open =true;
		node.sub.classList.toggle("active");

		this.editLabel ( node.nodes[newNodeName] );
		
		// Pass on to original callback
		this.addCallback(newNodeName, node);
	}
	
	deleteClicked(label, node)	{
		// Check callback returns true to indicate can delete node
		// this.deleteCallback(node);
		let path = node.pathToNode.split(".");
		console.log(path);
		let nodeRoute = this.structure[path[0]];
		for ( let i = 1; i < path.length-1; i++ )	{
//			console.log(nodeRoute);
//			console.log(path[i]);
			nodeRoute = nodeRoute.nodes[path[i]];
		}
		console.log(nodeRoute);
		node.labelLI.parentNode.removeChild(node.labelLI);
		delete nodeRoute.nodes[label];
		this.updateCallback(this.structure);
	}
}