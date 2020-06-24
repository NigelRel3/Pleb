class TransferDef extends TabPage	{
	constructor( div, workspace, label )	{
		super( div, workspace, label );
		this.dropArea = null;
		this.currentType = null;
		this.fieldDefs = null;
	}
	
	display(data)	{
		this.data = data;
		
		this.enableDragDrop();
		this.displayTypeInfo();
		this.displayDataInTab();
		
		this.getByDataID(this.div,'saveResourceDefinition').onclick =
			this.saveTab.bind(this);
	}
	
	enableDragDrop()	{
		// Drag and drop for sample file
		this.dropArea = this.getByDataID(this.div,'fileSample');
		['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
			this.dropArea.addEventListener(eventName, this.preventDefaults.bind(this), false)
		});
		['dragenter', 'dragover'].forEach(eventName => {
			this.dropArea.addEventListener(eventName, this.highlight.bind(this), false);
		});

		['dragleave', 'drop'].forEach(eventName => {
			this.dropArea.addEventListener(eventName, this.unhighlight.bind(this), false)
		});

		this.dropArea.addEventListener('drop', this.handleDrop.bind(this), false)
		
		// Set resource type options
		this.getByDataID(this.div,'ResourceType').onclick =
			this.displayTypeInfo.bind(this);
		
		this.getByDataID(this.div,'fileElem').addEventListener('change', (event) => {
		    this.handleFiles(event.target.files);
		  });
	}
	
	highlight() {
		this.dropArea.classList.add('highlight');
	}
	
	unhighlight() {
		this.dropArea.classList.remove('highlight');
	}
	
	preventDefaults (e) {
		e.preventDefault()
		e.stopPropagation()
	}
	
	handleDrop(e) {
		let dt = e.dataTransfer;
		let files = dt.files;

		this.handleFiles(files);
	}
	
	handleFiles(files) {
		// Read file for format
		const file = new FileReader();
		file.addEventListener('load', (event) => {
			this.displaySampleCSV (event.target.result);
		});
		file.readAsText(files[0]);
	}
	
	displayTypeInfo()	{
		if ( this.currentType != null )	{
			this.getByDataID(this.div,'fdResourceType_'+ this.currentType).style.display = "none";
		}
		this.currentType = this.getByDataID(this.div,'ResourceType').value;
		this.getByDataID(this.div,'fdResourceType_'+ this.currentType).style.display = "block";
	}
	
	displaySampleCSV ( fileText )	{
		const separator = this.getByDataID(this.div,'CSVSep').value;
		// Limit number of rows displayed
		let fileData = fileText.split("\n").slice(0,3);
		const header = fileData.shift().split(separator);
		const sampleDiv = this.getByDataID(this.div,'sampledata');
		sampleDiv.innerHTML = '';
		let sampleTable = document.createElement("table");
		sampleDiv.appendChild(sampleTable);
		sampleTable.appendChild(this.buildRow("th", header));
		for ( let row = 0; row < fileData.length; row++ ){
			sampleTable.appendChild(this.buildRow("td", 
					fileData[row].split(separator)));
		}
		
		if ( this.data.fieldDefs == null || this.data.fieldDefs.length == 0 )	{
			this.data.fieldDefs = [];
			for ( let i = 0; i < header.length; i++ ){
				this.data.fieldDefs[i] = { name: header[i].trim(),
						type: 'STRING', format: ''};
			}
			this.displayTable(this.getByDataID(this.div,'defTableBody'), 
					this.data.fieldDefs);
		}
	}
	
	buildRow ( rowType, values )	{
		let row = document.createElement("tr");
		for ( let i = 0; i < values.length; i++ ){
			let col = document.createElement(rowType);
			col.innerHTML = values[i].trim();
			row.appendChild(col);
		};
		return row;
	}
	
//	uploadFile(file) {
//		  let url = 'YOUR URL HERE'
//		  let formData = new FormData()
//
//		  formData.append('file', file)
//
//		  fetch(url, {
//		    method: 'POST',
//		    body: formData
//		  })
//		  .then(() => { /* Done. Inform the user */ })
//		  .catch(() => { /* Error. Inform the user */ })
//	}
	
	static getTemplate () {
		let newContent = document.getElementById("trHTMLDef").cloneNode(true);
		// Modify template to remove the id
		newContent.removeAttribute("id");
		newContent.removeAttribute("style");
		return newContent.outerHTML;

	}
	
	getByDataID ( base, name )	{
		return base.querySelector("[data-id='" + name + "']");
	}
}