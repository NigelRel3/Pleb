class FileDefinition extends TabPage	{
	constructor( div, workspace, label )	{
		super( div, workspace, label );
		this.dropArea = null;
	}
	display()	{
		this.dropArea = document.getElementById('fileSample');
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
	}
	
	highlight(e) {
		this.dropArea.classList.add('highlight');
	}
	unhighlight(e) {
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
		([...files]).forEach(this.uploadFile);
	}
	
	uploadFile(file) {
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
	}
	
	static getTemplate () {
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
			    	<label for="fdResourceType">Source Type</label>
			    	<select id="fdResourceType" data-id="ResourceType">
			    		<option value="CSV">CSV</option>
			    		<option value="JSON">JSON</option>
			    		<option value="XML">XML</option>
			    		<option value="MySQL">MySQL</option>
			    	</select>
			    	<br />
			    	<br />
		    		<button id="saveResourceDefinition" type="submit">Save</button>
			    </div>
			    <div class="resourceDefRight">
			    	<div id="fileSample">
			    		<p>Upload file dialog or by dragging and dropping onto dashed region</p>
			    		<input type="file" id="fileElem" onchange="handleFiles(this.files)">
			    	</div>
			    </div>
			    <div class"clearfix"></div>
			</div>
	    </form>
	    </div>`;
	}
}