class FileDefinition extends TabPage	{
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
//		let newContent = document.getElementById("fdHTMLDef").cloneNode(true);
		// Modify template to remove the id
//		newContent.removeAttribute("id");
//		newContent.removeAttribute("style");
//		return newContent.outerHTML;
		
		return `
	<div class="tabcontainer">
		<form>
			<div>
				<div class="resourceDefLeft">
					<label for="resourceName">Resource Name</label>
			    	<input type="text" placeholder="Enter Resource Name" id="resourceName" 
			    			data-id="resourceName" required>
					<br />
			    	<label for="rfdescription">Description</label>
			    	<textarea id="rfdescription" rows="4" cols="40" 
			    			data-id="Description"></textarea>
					<br />
		    		<button data-id="saveResourceDefinition" type="submit">Save</button>
		    	</div>
		    	<div>
				    <div class="resourceDefRight">
				    	<div data-id="fileSample">
				    		<p>Add sample file</p>
				    		<input type="file" data-id="fileElem" />
				    	</div>
				    	<label for="sampledata">Sample Data</label>
				 		<div data-id="sampledata" class="dataDisplay">No data to show</div>
				    </div>
			    </div>
			    <div class="clearfix"></div>
			    <br />
			    <div>
			    	<label for="fdResourceType">Source Type</label>
			    	<select data-id="ResourceType">
			    		<option value="CSV">CSV</option>
			    		<option value="JSON">JSON</option>
			    		<option value="XML">XML</option>
			    		<option value="Database">Database</option>
			    		<option value="Table">Table</option>
			    	</select>
			    	
				    <br />
				    <br />
			    	<div data-id="fdResourceType_CSV" style="display: none;">
						<label for="CSVSep">Separator</label>
				    	<input type="text" data-id="CSVSep" required size="4" value=",">
				    	<br />
				    	<br />
			    	</div>
			    	<div data-id="fdResourceType_JSON" style="display: none;">
			    		JSON
			    	</div>
			    	<div data-id="fdResourceType_XML" style="display: none;">
			    		XML
			    	</div>
			    	<div data-id="fdResourceType_Database" style="display: none;">
			    	    Database
			    	</div>
			    	<div data-id="fdResourceType_Table" style="display: none;">
						Table
			    	</div>
			    	<div class="dataDisplay">
		    			<p>Field definitions:</p>
			    		<table data-id="fieldDefs">
			    			<thead>
				    			<tr>
				    				<th>Name</th>
				    				<th>Type</th>
				    				<th>Format</th>
			    				</tr>
		    				</thead>
		    				<tbody data-id="defTableBody">
		    					<tr data-id="defTemplate" style="display:none">
			    					<td><input data-id="name" name="name" type="text" 
			    							maxlength="30" required="required"/>
			    					</td>
			    					<td><select data-id="type" name="type" required="required">
			    							<option value="INT">INT</option>
			    							<option value="STRING">STRING</option>
			    							<option value="DATE">DATE</option>
			    							<option value="ENUM">ENUM</option>
			    						</select>
			    					</td>
			    					<td><input data-id="format" name="format" type="text" 
			    							maxlength="30" />
	    							</td>
			    				</tr>
		    				</tbody>
			    		</table>
			    	</div>
			    </div>
			</div>
	    </form>
	</div>
`;
	}
	
	getByDataID ( base, name )	{
		return base.querySelector("[data-id='" + name + "']");
	}
}