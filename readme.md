# Laravel AutoDoc plugin 

This plugin is designed to gather information and generate documentation about 
your Rest-Api while passing the tests. The principle of operation is based on 
the fact that the special Middleware installed on the Route for which you want 
to collect information that after the successful completion of all tests 
generated Swagger-file. In addition this plug-in is able to draw Swagger-template 
to display the generated documentation for a config.

## Instalation

### Composer
 1. Add to required `"ronasit/laravel-swagger": "master-dev"`
 1. Run `composer update`

### Laravel
 1. Add **AutoDocServiceProvider::class** to providers in *config/app.php*
 1. Run `php artisan vendor:publish`
 
### Plugin
 1. Add middleware **AutoDocMiddleware::class** to *Http/Kernel.php*.
 1. Add **AutoDocRequestTrait** to your parent Request in *Http/Requests/Request.php*
 1. Set **AutoDocTestCase** as parent of your TestCase in *tests/TestCase.php*
 1. In *config/auto-doc.php* you can specify enabling of plugin, info of your project, 
 some defaults descriptions and route for rendering of documentation.
 1. In *.env* file you should add following lines
    `
    SWAGGER_FILEPATH_PRODUCTION=/example-folder/documentation.json  
    SWAGGER_FILEPATH_TEMP=/tmp/documentation.json
    `

## Usages
 For correcting work of plugin you have to dispose all the validation rules in the rules() method of class YourRequest, 
 which must be connected to the controller via DependencyInjection. In annotation of custom request you can specify 
 summary and description of this request. Plugin will take validation rules from your request and use it as description 
 of input parameter. 
  
### Example

 ```php
 <?php
 
 namespace App\Http\Requests;  
   
 use Illuminate\Contracts\Auth\Guard;  
   
 /**
  * @summary Updating of user
  *
  * @description
  *  This request mostly needed to specity flags <strong>free_comparison</strong> and 
  *  <strong>all_cities_available</strong> of user
  *
  * @_204 Successful MF!
  */
 class UpdateUserDataRequest extends Request
 {
     /**
      * Determine if the user is authorized to make this request.
      *
      * @return bool
      */
     public function authorize(Guard $guard)
     {
         return $guard->check();
     }  
   
     /**
      * Get the validation rules that apply to the request.
      *
      * @return array
      */
     public function rules()
     {
         return [
             'all_cities_available' => 'boolean',
             'free_comparison' => 'boolean'
         ];
     }
 }

 ```
 
 - **@summary** - short description of request
 - **@description** - Implementation Notes
 - **@_204** - Custom description of code of response. You can specify any code if you wish.
 
 If you do not create a class Request, the summary, Implementation Notes and parameters will be empty. 
 Plugin will collect codes and examples of responses only.
 
 If you do not create annotations to request summary will generate automatically from Name of Request.
 For example request **UpdateUserDataRequest** will have summary **Update user data request**.  
 
 If you do not create annotations for descriptions of codes it will be generated automatically the following priorities:
 1. Annotations of request
 2. Default description from *auto-doc.defaults.code-descriptions.{$code}*
 3. Descriptions from **Symfony\Component\HttpFoundation\Response::$statusTexts**
  
  Note about configs:  
 - *auto-doc.route* - it's a route where will be located generated documentation  
 - *auto-doc.basePath* - it's a route where located root of your api
 
## License

Auto-doc plugin is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
