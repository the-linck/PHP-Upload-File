# PHP-Server-Request

This library s meant to simplify PHP's file upload logic, making easier to deal with single or multiple files upload.


LikeTo simplify names and enhance organization, everything is kept on **\Http** namespace - the same as [PHP-Server_Request](https://github.com/the-linck/PHP-Server-Request). This file will always refer to classes with a fully qualified name.

---

## \Http\File

This class encapsulates the File Upload logic.

### Fields

All provided fields are read-only.

* *string* **Name**  
The original name of the file on the client machine
* *string* **Type**  
The mime type of the file, checked on the server side instead of believing on what the browser says
* *int* **Size**  
The size, in bytes, of the uploaded file
* *string* **Path**  
The temporary path of the file in which the uploaded file was stored on the server or the updated path if the file was moved
* *string* **Moved**  
If the file was moved by Move() or Save() method



### Methods

These public methods are provided:

* static **GetSingle**(string *$HtmlName*) : \Http\File  
Encapsulates a single file from $_FILES, returning a \Http\File instance
* static **GetAll**(string *$HtmlName*) : *\Http\File[]*  
Encapsulates multiple files from $_FILES, returning an array with a \Http\File instance for each of them
* **__construct**(string *$name*, int *$size*, string *$tmp_name*)
Creates a new \Http\File instance, checking internally it's mime type
* **__toString**() : *string*  
Allows to read the file content typecasting the *\Http\File* itself to a string
* **Move**(string *$destination*) : *bool*  
Moves an uploaded file to a new location
* **Output**() : *int|false*  
Reads the contents of the file to the output
* **SaveAs**(string *$filename*) : *bool*  
Saves the contents of an uploaded file, alias to *Move*()
* **ToBase64**() : *string*  
Returns the Base64 string representation of the file content
* **ToBinaryArray**() : *int[]*  
Returns a binary array with the file content, or an empty array if the content can't be read
* **ToString**() : *string*  
Returns the string representation of the file content

---



## Exceptions
* **\Http\UploadException**  
An \Http\UploadException is thrown when an error is found on a \Http\File, making easier to spot upload errors


*\Http\UploadException* is based on [danbrown's comment on PHP's manual](https://www.php.net/manual/en/features.file-upload.errors.php).
