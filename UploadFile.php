<?php
namespace Http;



/**
 * Encapsulates the File Upload logic, providing more a more reasonable way to deal with the files.
 * 
 * @property-read string $Name The original name of the file on the client machine.
 * @property-read string $Type The mime type of the file, checked on the server side instead of believing on what the
 * browser says.
 * @property-read int $Size The size, in bytes, of the uploaded file.
 * @property-read string $Path The temporary path of the file in which the uploaded file was stored on the server or
 * the updated path if the file was moved.
 * @property-read string $Moved If the file was moved by Move() or Save() method.
 * 
 * @see FileException
 */
class File {
    /**
     * Encapsulates a single file from $_FILES, returning a File instance.
     * If multiple files were sent with $HtmlName, only the first will be taken.
     * 
     * @param string $HtmlName Name attribute of the Form Element used to submit the file
     * @return File
     */
    public static function GetSingle($HtmlName) {
        // Checking if there is a file
        if (!array_key_exists($HtmlName, $_FILES)) {
            throw new \BadFunctionCallException(
                "No file was uploaded with $HtmlName name attribute."
            );
        }

        // Gets only the first file if multiple were uploaded
        if (is_array($_FILES[$HtmlName]['name'])) {
            // Checking for upload errors
            if ($_FILES[$HtmlName]['error'][0] != UPLOAD_ERR_OK) {
                throw new FileException($_FILES[$HtmlName]['error']);
            }

            return new File(
                $_FILES[$HtmlName]['name'][0],
                $_FILES[$HtmlName]['size'][0],
                $_FILES[$HtmlName]['tmp_name'][0]
            );
        } else {
            // Checking for upload errors
            if ($_FILES[$HtmlName]['error'] != UPLOAD_ERR_OK) {
                throw new FileException($_FILES[$HtmlName]['error']);
            }

            return new File(
                $_FILES[$HtmlName]['name'],
                $_FILES[$HtmlName]['size'],
                $_FILES[$HtmlName]['tmp_name']
            );
        }
    }
    /**
     * Encapsulates multiple files from $_FILES, returning an array with a File instance for each of them.
     * Only reads a 2D $_FILES array, more complex structures won't work.
     * 
     * @param string $HtmlName Name attribute of the Form Element used to submit the file
     * @return File[]
     */
    public static function GetAll($HtmlName) {
        // Checking if there is a file
        if (!array_key_exists($HtmlName, $_FILES)) {
            throw new \BadFunctionCallException(
                "No file was uploaded with $HtmlName name attribute."
            );
        }

        /**
         * @var File[]
         */
        $Result = array();

        if (is_array($_FILES[$HtmlName]['name'])) {
            // Autistic loop made by PHP's *wonderful* multiple upload scheme
            foreach ($_FILES[$HtmlName]['error'] as $Index => $Error) {
                // Checking for upload errors on each file
                if ($Error != UPLOAD_ERR_OK) {
                    throw new FileException($Error);
                }

                $Result[] = new File(
                    $_FILES[$HtmlName]['name'][$Index],
                    $_FILES[$HtmlName]['size'][$Index],
                    $_FILES[$HtmlName]['tmp_name'][$Index]
                );
            }
        } else { // If there's only one file, will be added to array
            // Checking for upload errors
            if ($_FILES[$HtmlName]['error'] != UPLOAD_ERR_OK) {
                throw new FileException($_FILES[$HtmlName]['error']);
            }

            $Result[] = new File(
                $_FILES[$HtmlName]['name'],
                $_FILES[$HtmlName]['size'],
                $_FILES[$HtmlName]['tmp_name']
            );
        }

        return $Result;
    }



    /**
     * The original name of the file on the client machine.
     * 
     * @var string
     */
    protected $Name;
    /**
     * The mime type of the file, checked on the server side instead of believing on what the browser says.
     * 
     * @var string
     * @see Mimetype
     */
    protected $Type;
    /**
     * The size, in bytes, of the uploaded file.
     * 
     * @var int
     */
    protected $Size;
    /**
     * The temporary path of the file in which the uploaded file was stored on the server.
     * If the file was successfully moved by .Move() or .Save(), will contain the updated path.
     * 
     * @var string
     */
    protected $Path;
    /**
     * If the file was already moved using a call to either .Move() or .Save().
     * 
     * @var bool
     */
    protected $Moved;



    /**
     * Creates a new File instance, checking internally it's mime type.
     * 
     * @internal
     * @param string $name Original name of the file, aka $_FILES[{whathever}]['name']
     * @param int $size Size of the file, aka $_FILES[{whathever}]['size']
     * @param string $tmp_name Temporary path of the file on server, aka $_FILES[{whathever}]['tmp_name']
     */
    public function __construct($name, $size, $tmp_name) {
        $this->Name = $name;
        $this->Size = $size;
        $this->Path = $tmp_name;
        $this->Moved = false;

        $Type = @mime_content_type($tmp_name);
        $this->Type = is_string($Type) ? $Type : '';
    }
    /**
     * Magic method __get() to make the Response properties read-only.
     * 
     * @param string $name
     * @return mixed|null Property value if it exists, null else.
     */
    public function __get($name)
    {
        return property_exists($this, $name)
            ? $this->{$name}
            : null
        ;
    }
    /**
     * Allows reading the file content typecasting this object to a string.
     * If the conent can't be read as string, an empty string is returned.
     * 
     * @return string
     */
    public function __toString() {
        $Content = file_get_contents($this->Path);

        return is_string($Content) ? $Content : '';
    }



    /**
     * Moves an uploaded file to a new location. If the file is already moved, returns false.
     * 
     * @param string $destination The destination of the moved file.
     * @return bool
     */
    public function Move($destination) {
        if (!$this->Moved) {
            $Result = move_uploaded_file($this->Path, $destination);

            if ($Result) {
                $this->Path = $destination;
            }
        } else {
            $Result = false;
        }

        return $Result;
    }
    /**
     * Reads the contents of the file to the output.
     * 
     * @return int|false the number of bytes read from the file. If an error occurs, false is returned.
     */
    public function Output() {
        return readfile($this->Path);
    }
    /**
     * Saves the contents of an uploaded file. If the file is already moved, returns false.
     * Alias to .Move(string)
     * 
     * @param string $filename The name of the saved file.
     * @return bool
     */
    public function SaveAs($filename) {
        return $this->Move($filename);
    }
    /**
     * Returns the Base64 string representation of the file content.
     * If the content can't be read as string, an empty string is returned.
     * 
     * @return string
     */
    public function ToBase64() {
        $Content = file_get_contents($this->Path);
        
        return is_string($Content) ? base64_encode($Content) : '';
    }
    /**
     * Returns a binary array with the file content.
     * If the content can't be read as string, an empty array is returned.
     * 
     * @return int[]
     */
    public function ToBinaryArray() {
        $Content = file_get_contents($this->Path);
        
        return is_string($Content) ? unpack('N*', $Content) : array();
    }
    /**
     * Returns the string representation of the file content.
     * If the content can't be read as string, an empty string is returned.
     * 
     * @return string
     */
    public function ToString() {
        $Content = file_get_contents($this->Path);
        
        return is_string($Content) ? $Content : '';
    }
}

/**
 * An FileException is thrown when an error is found on a File, making easier to spot upload errors.
 * 
 * This is based on danbrown's comment on PHP's manual.
 * @see File
 * @see https://www.php.net/manual/en/features.file-upload.errors.php
 */
class FileException extends \Exception {
    public function __construct($code, $previous = null) {
        $message = $this->codeToMessage($code);
        parent::__construct($message, $code, $previous);
    }

    protected function codeToMessage($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }

        return $message;
    }
}


/**
 * Dummy container to represent common MIME types in a standard way. Useful to set Content-Type headers.
 * Not a lot a types described, currently types suited to data transmission and web apps.
 * 
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/POST
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
 */
abstract class MimeType {
    /**
     * Binary file.
     * 
     * @var string
     */
    const BINARY = 'application/octet-stream';
    /**
     * Cascading Style Sheets (CSS); .css files.
     * 
     * @var string
     */
    const CSS = 'text/css';
    /**
     * .csv files.
     * 
     * @var string
     */
    const CSV = 'text/csv';
    /**
     * Microsoft Excel; .xls files.
     * 
     * @var string
     */
    const EXCEL_2003 = 'application/vnd.ms-excel';
    /**
     * Microsoft Excel (OpenXML); .xlsx files.
     * 
     * @var string
     */
    const EXCEL_2007 = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    /**
     * Request: Each value is sent as a block of data ("body part"), with a user agent-defined delimiter ("boundary")
     * separating each part. The keys are given in the Content-Disposition header of each part.
     * 
     * @var string
     */
    const FORM_DATA = 'multipart/form-data';
    /**
     * .htm, .html files.
     * 
     * @var string
     */
    const HTML = 'text/csv';
    /*
     * JavaScript; .js, .mjs files.
     * 
     * Older text/javascript is obsoleted.
     * 
     * @var string
     */
    const JAVASCRIPT = 'application/javascript';
    /**
     * Request: JSON encoded data, be it an object, an array or a JSON string. Response: JSON encoded string.
     * It should be encoded in UTF-8 and always used double quotes for strings.
     * 
     * @var string
     */
    const JSON = 'application/json';
    /**
     * Lightweight Linked Data format basead on JSON that provides a way to help JSON data interoperate at Web-scale.
     * Ideal data format for programming environments, REST Web services, and unstructured databases.
     * 
     * @var string
     */
    const JSON_LD = 'application/ld+json';
    /**
     * OpenDocument spreadsheet document; .ods files.
     * 
     * @var string
     */
    const SPREADSHEET = 'application/vnd.oasis.opendocument.spreadsheet';
    /**
     * Request: Spaces are converted to "+" symbols, but no special characters are encoded. Response: .txt files.
     * 
     * @var string
     */
    const TEXT = 'text/plain';
    /**
     * Unknown file type.
     * 
     * @var string
     */
    const UNKNOW = 'application/octet-stream';
    /**
     * Request: Keys and values are encoded in key-value tuples separated by '&', with a '=' between the key
     * and the value.
     * Non-alphanumeric characters in both keys and values are percent encoded.
     * 
     * @var string
     */
    const URL_ENCODED = 'application/x-www-form-urlencoded';
    /**
     * XHTML; .xhtml files.
     * 
     * @var string
     */
    const XHTML = 'application/xhtml+xml';
    /**
     * XML not readable from casual users; .xml files.
     * 
     * @var string
     */
    const XML = 'application/xml';
    /**
     * XTML readable from casual users; .xml files.
     * 
     * @var string
     */
    const XML_PUBLIC = 'text/xml';
}