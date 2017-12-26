function status(text) {
    document.getElementById("status").innerText = text;
}

function setFileInputMime(file, type) {
    file.setAttribute("accept", "");
    if (type === "image" || type === "lineimage") {
        file.setAttribute("accept", "image/*");
    } else if (type === "video") {
        file.setAttribute("accept", "video/*");
    }
}

function getFileSelectStatusMessage(type) {
    switch (type) {
        case "":
            return "Vælg den fil du vil sende";
        case "image":
        case "lineimage":
            return "Vælg de billeder du vil sende";
        case "video":
            return "Vælg den film du vil sende";
    }

    return "Vælg en filtype";
}

function filetypeshow() {
    var type = document.getElementById("type").value;
    var description = document.getElementById("description");
    var file = document.getElementById("file");

    description.style.display = (type === "image" || type === "lineimage") ? "" : "none";

    setFileInputMime(file, type);
    status(getFileSelectStatusMessage(type));

    if (type === "") {
        file.disabled = true;
        return;
    }
    file.disabled = false;
}

function fileExistsResponse(data) {
    if (data.exists) {
        alert("Bemærk en fil med navnet \"" + data.name +
              "\" allerede eksistere, og vil blive overskrevet hvis du fortsætter med denne upload!");
    }
}

function validate() {
    var files = document.getElementById("file").files;
    var submit = document.getElementById("submit");

    submit.disabled = true;
    if (!files.length) {
        return false;
    }

    for (const file of files) {
        if (file.size > maxbyte) {
            alert("Filen må max være på " + Math.round(maxbyte / 1024 / 1024 * 10) / 10 + "MB");
            return false;
        }

        status("Søger efter dubletter...");

        var path = activeDir + "/" + file.name;
        var type = document.getElementById("type").value;
        xHttp.request(
            "/admin/explorer/files/exists/?path=" + encodeURIComponent(path) + "&type=" + encodeURIComponent(type),
            fileExistsResponse);
    }
    status("");

    submit.disabled = false;
    return true;
}

function uploadCompleated() {
    status("Filerne er sendt");
    window.opener.showfiles(window.opener.activeDir);
    document.getElementById("progress").style.display = "none";
    document.getElementById("type").disabled = false;
    document.getElementById("type").value = "";
    document.getElementById("file").value = "";
    document.getElementById("alt").value = "";
    validate();
}

var totals = [];
var uploads = [];
function updateProgress() {
    var totalSize = 0;
    for (const total of totals) {
        totalSize += total; // Set max before starting any uploads
    }

    var uploaded = 0;
    for (const upload of uploads) {
        uploaded += upload;
    }

    var progressBar = document.getElementById("progress");
    progressBar.max = parseInt(totalSize);

    var diff = totals.filter(function(element, index) {
        return element === uploads[index]
    });

    var statusText = " beregner upload...";
    if (uploaded) {
        progressBar.value = parseInt(uploaded);
        statusText = " uploader..."
    }
    status(diff.length + "/" + totals.length + statusText);

    if (progressBar.max === progressBar.value) {
        uploadCompleated();
    }
}

function getOnLoadFunction(x, i) {
    return function(data) {
        uploads[i] = totals[i];
        updateProgress();
        var result;
        try {
            result = JSON.parse(x.responseText);
        } catch (err) {
            result = {"uploaded": (x.status === 200 ? 1 : 0), "error": {"message": "Error: " + x.responseText}};
        }
        if (!result.uploaded) {
            alert(result.error.message);
        }
    };
}

function getOnProgressFunction(i) {
    return function(evt) {
        if (evt.lengthComputable) {
            totals[i] = evt.total;
        }
        uploads[i] = evt.loaded;
        updateProgress();
    };
}

function send() {
    status("Klargøre data.");
    totals = [];
    uploads = [];

    document.getElementById("type").disabled = true;
    document.getElementById("file").disabled = true;
    document.getElementById("submit").disabled = true;
    document.getElementById("description").style.display = "none";
    document.getElementById("description").style.display = "none";

    var progress = document.getElementById("progress");
    progress.removeAttribute("value")
    progress.style.display = "block";

    var index;
    var x;
    var file;
    var files = document.getElementById("file").files;
    var form = new FormData();
    form.append("type", document.getElementById("type").value);
    form.append("alt", document.getElementById("alt").value);
    form.append("dir", activeDir);

    for (const file of files) {
        form.append("upload", file);
        try {
            x = new window.XMLHttpRequest();
        } catch (e) {
            continue;
        }
        if (typeof x.readyState !== "number") {
            continue;
        }
        index = totals.push(files.size);
        uploads.push(NaN);
        x.onload = getOnLoadFunction(x, index - 1);
        x.upload.onprogress = getOnProgressFunction(index - 1);
        x.open("POST", "/admin/explorer/files/", true);
        x.send(form);
    }

    return false;
}
