<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users as UsersRepo;

final class Friends extends VKAPIRequestHandler
{
	function get(int $user_id, string $fields = "", int $offset = 0, int $count = 100): object
	{
		$i = 0;
		$offset++;
		$friends = [];

		$users = new UsersRepo;

		$this->requireUser();
		
		foreach ($users->get($user_id)->getFriends($offset, $count) as $friend) {
			$friends[$i] = $friend->getId();
			$i++;
		}

		$response = $friends;

		$usersApi = new Users($this->getUser());

		if (!is_null($fields)) {
			$response = $usersApi->get(implode(',', $friends), $fields, 0, $count, true);  // FIXME
		}

		return (object) [
			"count" => $users->get($user_id)->getFriendsCount(),
			"items" => $response
		];
	}

	function getLists(): object
	{
		$this->requireUser();

		return (object) [
			"count" => 0,
			"items" => (array)[]
		];
	}

	function deleteList(): int
	{
		$this->requireUser();

		return 1;
	}

	function edit(): int
	{
		$this->requireUser();

		return 1;
	}

	function editList(): int
	{
		$this->requireUser();

		return 1;
	}

	function add(string $user_id): int
	{
		$this->requireUser();

		$users = new UsersRepo;

		$user = $users->get(intval($user_id));
		
		if(is_null($user)){
			$this->fail(177, "Cannot add this user to friends as user not found");
		} else if($user->getId() == $this->getUser()->getId()) {
			$this->fail(174, "Cannot add user himself as friend");
		}

		switch ($user->getSubscriptionStatus($this->getUser())) {
			case 0:
				$user->toggleSubscription($this->getUser());
				return 1;
				break;

			case 1:
				$user->toggleSubscription($this->getUser());
				return 2;
				break;

			case 3:
				return 2;
				break;
			
			default:
				return 1;
				break;
		}
	}

	function delete(string $user_id): int
	{
		$this->requireUser();

		$users = new UsersRepo;

		$user = $users->get(intval($user_id));

		switch ($user->getSubscriptionStatus($this->getUser())) {
			case 3:
				$user->toggleSubscription($this->getUser());
				return 1;
				break;
			
			default:
				fail(15, "Access denied: No friend or friend request found.");
				break;
		}
	}

	function areFriends(string $user_ids): array
	{
		$this->requireUser();

		$users = new UsersRepo;

		$friends = explode(',', $user_ids);

		$response = [];

		for ($i=0; $i < sizeof($friends); $i++) { 
			$friend = $users->get(intval($friends[$i]));

			$status = 0;
			switch ($friend->getSubscriptionStatus($this->getUser())) {
				case 3:
				case 0:
					$status = $friend->getSubscriptionStatus($this->getUser());
					break;
				
				case 1:
					$status = 2;
					break;

				case 2:
					$status = 1;
					break;
			}

			$response[] = (object)[
				"friend_status" => $friend->getSubscriptionStatus($this->getUser()),
				"user_id" => $friend->getId()
			];
		}

		return $response;
	}
}