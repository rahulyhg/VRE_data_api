<?php

namespace App\Controllers;

use \App\Models\Mugfile as Mugfile;

class ApiController extends Controller {

    /**
     * @SWG\Get(
     *     path="/files",
     *     summary="List user files",
     *     tags={"files"},
     *     description="List MuG files accessible by the user. Result can be filtered and sorted",
     *     operationId="showUserMugfiles",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit number of MuG files returned",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort files by attribute",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful operation",
     *         @SWG\Schema(
     *             type="array",
     *             @SWG\Items(ref="#/definitions/Mugfile")
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Invalid request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Not authorized",
     *     ),
     *     security={
     *             {"bearer":{}}
     *     },
     *     deprecated=true
     * )
     **/

	public function get_files($request, $response, $args) {

        $userLogin = $request->getAttribute('userLogin');

        $filters = $request->getQueryParams(); #sort,fields,page,offset,..
        if($filters['limit'] && is_integer((int)$filters['limit']) )
            $limit=(int)$filters['limit'];
        if($filters['sort_by'])
            $sort_by=$filters['sort_by'];
        $files = $this->mugfile->getFiles($userLogin,$limit,$sort_by);

		echo json_encode($files,JSON_PRETTY_PRINT);

	}

    /**
     * @SWG\Get(
     *     path="/files/{id}",
     *     summary="Show Mugfile",
     *     tags={"files"},
     *     description="Show MuG file object",
     *     operationId="showMugfile",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="Mugfile identifier",
     *         required=true,
     *         type="string",
     *         default="MuGUSER5968cc290156d_5968cc2954a382.7980548"
     *     ),
     *     @SWG\Parameter(
     *         name="access_token",
     *         in="query",
     *         description="Access Token. If not found in header, accepted from URL query",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Successful operation",
     *         @SWG\Schema(
     *             ref="#/definitions/Mugfile"
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Invalid request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Not authorized",
     *     ),
     *     security={
     *             {"bearer":{}}
     *     },
     *     deprecated=false
     * )
     **/

    
	public function get_files_by_id($request, $response, $args) {

		$id        = $args['file_id'];
        $userLogin = $request->getAttribute('userLogin');

        if ($id){
				
			//$file = new Mugfile($this->container);
			//$file->getFile($id,$userLogin);
            $file = $this->mugfile->getFile($id,$userLogin);
		
			if (!$file->file_id){
				$code = 404;
                $errMsg = "Resource not found. Id=$id; Repository=".$this->global['local_repository'].";";
                if ($userLogin)
                    $errMsg .= " Login=$userLogin;";
				throw new \Exception($errMsg, $code); 
			}
	
			echo json_encode($file,JSON_PRETTY_PRINT);
		
		}
		//return $response;

	}


    /**
     * @SWG\Get(
     *     path="/content/{file_path}",
     *     summary="Get file content",
     *     tags={"content"},
     *     description="Get file content given a the path in the repository ('file_path' in Mugfile object)",
     *     operationId="getFileContent",
     *     produces={"application/json","text/plain", "application/octet-stream","application/x-gzip","application/x-tar","application/zip","text/html","image/png","image/tiff"},
     *     @SWG\Parameter(
     *         name="file_path",
     *         in="path",
     *         description="Mugfile file_path",
     *         required=true,
     *         type="string",
     *         default="MuGUSER5968cc290156d\/uploads\/README.md"
     *     ),
     *     @SWG\Parameter(
     *         name="access_token",
     *         in="query",
     *         description="Access Token. If not found in header, accepted from URL query",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Returns content file",
     *         @SWG\Schema(
     *             type="file"
     *         ),
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="Invalid request",
     *     ),
     *     @SWG\Response(
     *         response="401",
     *         description="Not authorized",
     *     ),
     *     security={
     *             {"bearer":{}}
     *     },
     *     deprecated=false
     * )
     **/

    public function get_content_by_path($request, $response, $args) {

        $file_path = $request->getAttribute('file_path');  // path as returned by DM API (relative to user root dir)

        $mug_id    = $request->getAttribute('mug_id');     // mug_id used to verify that token owner is resource onwner
        $userLogin = $request->getAttribute('userLogin');  // user mail used to get mug_id via internal VRE db. //USABILITY RESTRICTED TO DB ACCESSIBILITY

        $filters   = $request->getQueryParams(); #sort,fields,page,offset,.. //TODO
        
        if ($file_path){
            $path = explode('/', $file_path);

            if (!count($path) || in_array("..",$path) || in_array(".",$path) ){
				$code = 400;
                $errMsg = "Invalid file path. Id=$file_path; Repository=".$this->global['local_repository'].";";
                if ($userLogin)
                    $errMsg .= " Login=$userLogin;";
				throw new \Exception($errMsg, $code); 
            }
            $user_rootDir = $path[0];
    
            // Check resource owner
            if (!$mug_id && $userLogin){
               if (!$this->user->userExists($userLogin)){
                    $code = 401;
                    $errMsg = "User $userLogin does not exist. Id=$file_path; Repository=".$this->global['local_repository'].";";
                    throw new \Exception($errMsg, $code);
               }
               $user = $this->user->getUser($userLogin);
               $mug_id = $user->id;
            }
            if ($mug_id){
                if ($user_rootDir != $mug_id){
                    $code = 401;
                    $errMsg = "User $userLogin ($mug_id) not allowed to access file. Id=$file_path; Repository=".$this->global['local_repository'].";";
                    throw new \Exception($errMsg, $code);
                }
            }else{
                $code = 401;
                $errMsg = "No resource owner information. Id=$file_path; Repository=".$this->global['local_repository'].";";
                throw new \Exception($errMsg, $code);
            }

			// Get full path
			$rfn = $this->global['dataDir'].$file_path;

            // Return file
            if (is_file($rfn)){
    			$fileExtension = $this->utils->getExtension($rfn);
    			$mimeTypes     = $this->utils->mimeTypes();
    			$contentType = (array_key_exists($fileExtension, $mimeTypes)?$mimeTypes[$fileExtension]:"text/plain");
    			$disposition  = "attachment"; // attachment | inline

                readfile($rfn);
    			$response = $response->withHeader('Content-Type', 'text/plain');
            }
            // Return directory
            elseif(is_dir($rfn)){
                print scandir($rfn);
            }
            else{
				$code = 422;
				$errMsg = "Resource not available. Id=$file_path; Repository=".$this->global['local_repository']."; File_path=".$rfn.";";
		    	$this->logger->error("code $code: error: $errMsg");
                throw new \Exception($errMsg, $code);
				//$response = $response->withStatus($code);
				//$response = $response->withHeader('Content-Type','application/json');
				//$response = $response->withJson(['error' => $errMsg,'code'   => $code]);
				//return $response;
				
            }
        }
		return $response;
    }


	public function get_content_by_id($request, $response, $args) {

        $id   = $args['file_id'];
        $userLogin = $request->getAttribute('userLogin');

        if ($id){

			//get file
			$file = new Mugfile($this->container);
			$file->getFile($id,$userLogin);

			if (!$file->file_id){
				$code = 404;
                $errMsg = "Resource not found. Id=$id; Repository=".$this->global['local_repository'].";";
                if ($userLogin)
                    $errMsg .= " Login=$userLogin;";
				throw new \Exception($errMsg, $code); 
			}


			if (!$file->file_path){
				$code = 422;
				$errMsg = "Attribute 'path' not defined. Id=$id; Repository=".$this->global['local_repository'].";";
                if ($userLogin)
                    $errMsg .= " Login=$userLogin;";
				throw new \Exception($errMsg, $code); 
				
			}

			// Full path
			$rfn = $this->global['dataDir'].$file->file_path;

			if (!is_file($rfn)){
				$code = 422;
				$errMsg = "Resource not available. Id=$id; Repository=".$this->global['local_repository']."; File_path: ".$file->file_path. "; Absolute_path: $rfn;";
			    	$this->logger->error("code $code: error: $errMsg");
				$response = $response->withStatus($code);
				$response = $response->withHeader('Content-Type','application/json');
				$response = $response->withJson(['error' => $errMsg,'code'   => $code]);
				return $response;
				
			}

			$fileExtension = $this->utils->getExtension($file->file_path);
			$mimeTypes     = $this->utils->mimeTypes();
			$contentType = (array_key_exists($fileExtension, $mimeTypes)?$mimeTypes[$fileExtension]:"text/plain");
			$disposition  = "attachment"; // attachment | inline


/*
			header('Content-Length: ' . filesize($rfn));
			header('Content-Description: File Transfer');
			header('Transfer-Encoding: identity');
			header('Content-Type: '.$contentType);
			header('Content-Disposition: '.$disposition.';filename="'.basename($rfn).'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
*/
			readfile($rfn);
			$response = $response->withHeader('Content-Type', 'text/plain');

/*
			$response = $response->withHeader('Content-Description', 'File Transfer')
					->withHeader('Content-Type', 'application/octet-stream')
					->withHeader('Content-Disposition', 'attachment;filename="'.basename($rfn).'"')
					->withHeader('Expires', '0')
					->withHeader('Cache-Control', 'must-revalidate')
					->withHeader('Pragma', 'public')
					->withHeader('Content-Length', filesize($rfn));
		
			//readfile($rfn);
			print passthru("/bin/cat \"$rfn\"");
			exit(0);
*/
		}

		return $response;
	}


}

