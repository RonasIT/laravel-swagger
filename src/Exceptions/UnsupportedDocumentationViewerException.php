<?php

namespace RonasIT\Support\AutoDoc\Exceptions;

use Exception;

class UnsupportedDocumentationViewerException extends Exception
{
    public function __construct(string $invalidViewer)
    {
        parent::__construct(
            "The documentation viewer '{$invalidViewer}' does not exists."
             . " Please check that the 'documentation_viewer' key of your auto-doc.php config has one of valid values."
        );
    }
}
