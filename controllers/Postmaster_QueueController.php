<?php
namespace Craft;

class Postmaster_QueueController extends BaseController
{
	protected $allowAnonymous = true;

	public function actionMarshal()
	{
		craft()->postmaster_queue->marshal();
	}
}