<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Multiple Images with Drag-and-Drop</title>
    <style>
       body {
           font-family: sans-serif;
           background-color: #f7f7f7;
           text-align: center;
           color: #242424;
       }
       .file-upload {
           background-color: #ffffff;
           width: 600px;
           margin: 20px auto;
           padding: 20px;
           border-radius: 8px;
           border: 2px dashed #0163B8;
           position: relative;
       }
       .file-upload.dragover {
           background-color: #e0ffe5;
       }
       .file-upload-btn, .file-submit-btn {
           color: #fff;
           background: #0163B8;
           border: none;
           padding: 10px;
           border-radius: 4px;
           text-transform: uppercase;
           font-weight: 700;
           cursor: pointer;
           margin-top: 10px;
           width: 100%;
       }
       .file-submit-btn { width: 50%; }
       .file-upload-content {
           display: flex;
           flex-wrap: wrap;
           gap: 10px;
           margin-top: 20px;
           justify-content: center;
       }
       .image-preview {
           position: relative;
           max-width: 150px;
           margin: 10px;
       }
       .image-preview img {
           width: 100%;
           border-radius: 4px;
           box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
       }
       .remove-image-btn {
           position: absolute;
           top: 5px;
           right: 5px;
           background: #cd4535;
           color: white;
           border: none;
           border-radius: 50%;
           width: 24px;
           height: 24px;
           font-weight: bold;
           cursor: pointer;
           text-align: center;
       }
       .drag-text h3 {
           font-weight: 100;
           text-transform: uppercase;
           color: #0186CD;
           padding: 40px 0;
       }
    </style>
</head>
<body>
    <h2>Upload Multiple Images with Drag-and-Drop</h2>
    <form id="uploadForm" action="process.php" method="post" enctype="multipart/form-data">
    <div class="file-upload" id="dropArea">
        <button class="file-upload-btn" type="button" onclick="document.getElementById('fileInput').click()">Add Images</button>
        <input id="fileInput" name="image[]" type="file" accept="image/*" multiple style="display:none" onchange="handleFiles(this.files)">
        
        <div class="drag-text">
            <h3>Drag and drop files here or click to select</h3>
        </div>
        
        <div class="file-upload-content" id="imagePreviewContainer"></div>
        <button type="button" class="file-submit-btn">Submit</button>
    </div>

    </form>

    <script>
    const dropArea = document.getElementById('dropArea');
    const inputElement = document.getElementById('fileInput');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');

    // Drag and drop events
    ['dragenter', 'dragover'].forEach(event => {
        dropArea.addEventListener(event, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(event => {
        dropArea.addEventListener(event, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropArea.classList.remove('dragover');
        });
    });

    dropArea.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    // Preview and remove functionality
    function handleFiles(files) {
        const fileArray = Array.from(files);
        imagePreviewContainer.innerHTML = ''; // Clear previous previews

        fileArray.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = (e) => {
                const imgContainer = document.createElement('div');
                imgContainer.classList.add('image-preview');

                const img = document.createElement('img');
                img.src = e.target.result;
                imgContainer.appendChild(img);

                const removeButton = document.createElement('button');
                removeButton.classList.add('remove-image-btn');
                removeButton.innerHTML = '&times;';
                removeButton.onclick = () => removeImage(index);

                imgContainer.appendChild(removeButton);
                imagePreviewContainer.appendChild(imgContainer);
            };

            reader.readAsDataURL(file);
        });

        inputElement.files = files; // Update input element with new file list
    }

    // Remove image function
    function removeImage(index) {
        const dt = new DataTransfer();
        Array.from(inputElement.files).forEach((file, i) => {
            if (i !== index) dt.items.add(file); // Keep files except the removed one
        });
        inputElement.files = dt.files; // Update input element

        handleFiles(inputElement.files); // Refresh previews
    }

    // Submit form with selected files
    document.querySelector('.file-submit-btn').addEventListener('click', () => {
        const form = document.getElementById('uploadForm');

        if (inputElement.files.length === 0) {
            showToast('Please select at least one image to upload.', true);
            return;
        }

        form.submit(); // This will submit the form to process.php
    });
</script>

</body>

</html>
