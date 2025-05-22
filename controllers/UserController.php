<?php

namespace Controllers;

use Utils\Request;
use Utils\Response;
use Utils\Validator;
use Services\UserService;

class UserController
{
    private $userService;

    public function __construct()
    {
        $this->userService = new UserService();
    }

    public function index(Request $request)
    {
        $currentPage = intval($request->getQueryParam('page') ?? 1); // Get 'page' from request or default to page 1
        $itemPerPage = intval($request->getQueryParam('limit') ?? 10); // Get 'itemsPerPage' from request or default to 1
        $users = $this->userService->getAllUsers($currentPage, $itemPerPage);
        return Response::success([
            'current_page' => $users->getCurrentPage(),
            'total_pages' => $users->getTotalPages(),
            'items' => $users->getPageData()
        ]);
    }

    public function show(Request $request, $params)
    {
        $user = $this->userService->getUserById($params['id']);

        if (!$user) {
            return Response::notFound('User not found');
        }

        return Response::success($user);
    }

    public function profile()
    {
        $profile = $this->userService->getUserById($_REQUEST['user_id']);

        if (!$profile) {
            return Response::notFound('Account not found');
        }

        return Response::success($profile);
    }

    public function store(Request $request)
    {
        $body = $request->getBody();

        $validator = new Validator($body);
        $validator->required('first_name')
            ->required('last_name')
            ->required('phone_number')
            ->phoneNumber('phone_number')
            ->required('address')
            ->required('email')
            ->email('email')
            ->required('password')
            ->min('password', 8)
            ->alphaNumeric('password');

        if (!$validator->isValid()) {
            return Response::validationError($validator->getErrors());
        }

        $event = $this->userService->createUser($body);
        return Response::success($event, 'User created successfully', 201);
    }

    public function update(Request $request, $params)
    {
        $body = $request->getBody();

        $validator = new Validator($body);

        if(isset($body['phone_number'])) {
            $validator->phoneNumber('phone_number');
        }
        if (isset($body['address'])) {
            $validator->required('address');
        }

        if (!$validator->isValid()) {
            return Response::validationError($validator->getErrors());
        }

        $event = $this->userService->updateUser($params['id'], $body);

        if (!$event) {
            return Response::notFound('User not found');
        }

        return Response::success($event, 'Events updated successfully');
    }

    // public function destroy(Request $request, $params)
    // {
    //     $result = $this->userService->deleteUser($params['id']);

    //     if (!$result) {
    //         return Response::notFound('User not found');
    //     }

    //     return Response::success(null, 'User deleted successfully');
    // }

    public function login(Request $request)
    {
        $body = $request->getBody();

        $validator = new Validator($body);
        $validator->required('email')
            ->email('email')
            ->required('password');

        if (!$validator->isValid()) {
            return Response::validationError($validator->getErrors());
        }

        $result = $this->userService->login($body['email'], $body['password']);

        if (!$result) {
            return Response::unauthorized('Invalid credentials');
        }

        return Response::success($result, 'Login successful');
    }

    public function register(Request $request)
    {
        return $this->store($request);
    }
}
