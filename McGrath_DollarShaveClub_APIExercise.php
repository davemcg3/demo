<?php 
/**
 * 
 * Dollar Shave Club API Exercise
 * Author: David McGrath
 * Authored on: 20170501
 * 
 * Broad requirements:
 * Social Media Site like FB or Twitter
 * One service is API that allows 3rd party to access data about our social graph
 * RESTFUL
 * Framework similar to Ruby on Rails
 * App partners want to receive notifications when new users subscribe to pages or like posts on their page
 * We want to give push notifications but charge based on volume of notifications.
 * 
 * Fields we might need in the model:
 * id,
 * message
 * created,
 * seen
 * page id,
 * notificationSent,
 * 
 * I would approach with a PubSub pattern to decouple the creation of the event from the dispatch.
 * Receive a notification like (or post) happened, store that into the database
 * Watcher script loops through db looking for new events and sends notification
 * Didn't implement that pattern here, but we discussed it extensively on the call.
 * 
 * Example:
 * Delta (company) has a page on the social media site
 * Someone posts to Delta's page
 * Send a Push to Delta
 * 
 * Restful HTTP methods:
 * POST - Create
 * GET - Read
 * PUT - Update/Replace
 * PATCH - Update/Modify
 * DELETE - Delete
 */

class PostApiModel
{
	var $params = ["id","message","created","seen","page_id","notificationSent"];
	//might want to use strong types on these to prevent errors, but Ruby and PHP are dynamically
	//typed languages so we can just reassign types as needed
	var $id =  '',
	$message = '',
	$created = '',
	$seen =  '',
	$page_id =  '',
	$notificationSent = '';

	function __construct($params)
	{
		foreach ($params as $key => $value)
		{
			$this->$key = $value;
		}
		//you might want to call a persistData here, but you might not want to depending on the 
		//project constraints.  Here I've chosen to do it separately, but usually I would 
		//probably save early and often.
	}

	function persistData()
	{
		//wire this up to your database with PDO statements or external API with an implementation
		//of those connectors.  Out of scope for this exercise, so just assuming it worked
		return true;
	}

	function update($field, $timestamp)
	{
		$this->$field = $timestamp;
		$this->persistData();
	}

	function display()
	{
		//This outputs a visual representation of the model for command line viewing
		print "Post " . $this->id . " posted to page id " . $this->page_id . " created at " . $this->created;
		if ($this->notificationSent !== '')
			print " had a notification sent at " . $this->notificationSent;
		if ($this->seen !== '')
			print "and it was seen at " . $this->seen;
		print ".  The post message is: " . $this->message . "\n";
	}
}


class postApiController {

	var $code = 200;
	var $message = "OK";
	var $failed_fields = array();
	var $post_object = null;

	function __construct()
	{
		//could set default data or settings here
	}
	
	//stub for generating a new instance variable of PostApi type, made available to the view
	function new()
	{
	}

	//persists PostApi data to the model
	function create($params)
	{
		//mocking rails strong params functionality so we can dump the params we're not explicitly expecting
		$sanitized_params = $this->sanitize_params($params);

		//validate data
		//in rails this is usually done in the model with ActiveRecord (or other appropriate class) validations
		//doing it here instead for simplicity of the exercise
		if (empty($this->validation_piece($sanitized_params)))
		{
			//instantiate
			$this->postObject = new PostApiModel($sanitized_params);

			//in the model
			if (!$this->postObject->persistData())
			{
				$this->code = 500;
				$this->message = "Problem persisting your post.  Please try again.\n";
			} else
			{

				//like we talked about we would want to refactor this piece out into it's own asynchronous script
				//so that someone else's slow API isn't slowing down our service and blocking our script's return.
				//leaving here because of the scope of the exercise
				if (!$this->sendPush($this->postObject, false))
				{
					$this->code = 500;
					$this->message = "Problem sending push notification.\n";
				}

				//You might want to set a message to display on the view here, or you could set it explicitly in the view, which
				//is what I've done here for simplicity

			}

		} else 
		{
			//failure message to user
			$this->code = 400; //Probably don't show this to the user, but it's here to do something sensible with it in the view
			$this->message = "Problem with the following fields: ";
			$count = 0;
			foreach ($this->failed_fields as $field)
			{
				if ($count++ !== 0) $this->message .= ", ";
				$this->message .= "$field";
			}
			$this->message .= "\n";
		}

		//render view however the framework does it.  In Rails if you give it the same name it goes automatically, or you
		//can specify a different view like `render otherViewName' but in vanilla PHP we don't really have that, so a dirty
		//hack might be to just include the view file.  Commented it out because we don't have a file there.  We'll mock it
		//with a function call
		//include('../views/postApiView.php');
		$this->renderView();

	}

	function seenPostback($id, $timestamp)
	{
		//you would need a postback from the client interface to let us know when the notification was seen
		//This code could live within the edit() stub, but since it's a Postback for a specific field I
		//am putting it in it's own endpoint that we could build routes to.
		//you would need to build out code to build a PostApiModel object from the db when passed an id
		//and then we would use the update functionality in the model to update the $seen field
		//I'm not writing out the code to build the object (but I could), but that's roughly the flow.  It 
		//might look something like this: 
		//$post = new PostApiModel($id);
		//$post->update('seen', $timestamp);

		//I'm going to cheat it here since I already have the object:
		$this->postObject->update('seen', $timestamp);

		//and then maybe output something to a log somewhere and return a JSON 200 'OK' to the caller (or failure code if it failed)
	}

	private function sendPush($post, $send=true)
	{
		//throwing this override in for simple testing
		if ($send)
		{
			//destination webhook stored on page, API
			$destination = 'http://wherever.thehookgoes.io';

			//curl has a bunch of options we could set here
			//this code won't work, for display purposes only
			//I can find working curl code online and adapt it for us
			$curl = curl_init($destination);
			$curl->setOptions('POST', true);
			$curl->execute($destination, $post);
		}

		//in production we would want to do some sort of check for whether the send happened or didn't and return that
		$this->postObject->update('notificationSent', '2017-05-01 19:20');
		return true;
	}

	//method for showing a postApi
	function show()
	{
		$this->postObject->display();
	}

	//stubbed method for editing a postApi
	function edit()
	{

	}

	//stubbed method for deleting a postApi
	function destroy()
	{

	}

	private function renderView()
	{
		//since we are including this as a function in the controller I'm going to cheat and just access the class 
		//variables with $this, but I acknowledge that under normal circumstances they would need to be explicitly
		//passed in
		//here you can switch on the code and render different error templates for the different classes of errors
		//not doing that here because this is an exercise, not production code
		if ($this->code !== 200)
		{
			//display error
			print $this->message;
		} else 
		{
			//display successful output
			print "Great success! Your post was saved and a push sent!\n";
			print $this->postObject->display();
		}
	}

	private function validation_piece($params)
	{
		//here we would write validation of parameters, maybe something like what follows
		//you can early exit when any single parameter fails with a return statement, but
		//that's frustrating for the user if they have to do many return trips so I prefer
		//to show all failures on each pass
		foreach($params as $key => $value)
		{
			switch($key)
			{
				case "id":
					if (!is_numeric($value))
						array_push($this->failed_fields, $key);
					break;
				case "message":
					//do something to make sure the field isn't empty or whatever makes
					//sense.
					if (empty($value))
						array_push($this->failed_fields, $key);
					break;
				case "created":
					//you can see the pattern at this point.  I would check to make sure
					//this is a date
					break;
				case "seen":
					//I would check to make sure this is a date
					break;
				case "page_id":
					//I would probably check for numericality
					break;
				case "notificationSent":
					//I would probably check for a date
					break;
				default:
					//shouldn't ever get here, but I usually add it for safety
					//getting here in general is a failure, so maybe throw an exception
					//or send an alert to a system administrator.  For here I'll just
					//throw the field that got here into the failed_fields array.
					array_push($this->failed_fields, $key);
			}
		}
		return $this->failed_fields; //for the if check in the calling function
	}

	private function sanitize_params ($params)
	{
 		$param_array = array("id","message","created","seen","page_id","notificationSent");
 		$sanitized_params = array();
		foreach ($param_array as $param)
		{
			if (key_exists($param, $params))
				$sanitized_params[$param] = $params[$param];
			else
				array_push($this->failed_fields,$param);
		}
		return $sanitized_params;
	}

}


//test implementation
$shavePost = new postApiController();

//intentional failure
print "Test 1, intended failure:\n";
$shavePost->create([]);
print "\n";

//successful post creation
print "Test 2, intended success:\n";
$shavePost = new postApiController();
$shavePost->create(["id"=>463,"message"=>"Being a great father is like shaving.  No matter how good you shaved today, you have to do it again tomorrow. -Reed Markham", "created"=>"2017-05-01 19:00", "seen"=>"", "page_id"=>511, "notificationSent"=>""]);
//add a postback
print "adding a notification seen postback\n";
$shavePost->seenPostback(463,'2017-05-01 19:25');
print "ok\n";
//show our updated post model
print "showing our updated post:\n";
$shavePost->show();
?>