<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\UserType;
use AppBundle\Repository\UserRepository;

use InvalidArgumentException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route("/user")
 */
class UserController extends Controller
{
  private $userRepository;

  /**
   * Handles the create user action.
   *
   * @Route("/")
   * @Route("")
   * @Method({"POST"})
   * @throws \Exception
   */
   public function createAction(Request $request) {
     $data = $this->getUserDataFromRequest($request);

     try {
       $user = $this->getUserRepository()->createUser($data);
     } catch(\Exception $e) {
       return new Response($e->getMessage(), 400);
     }

     $response = new Response('Created User with id ' . $user->getUserId() . '.', 201);
     $response->headers->set('Location', '/user/' . $user->getUserId());

     return $response;
   }

  /**
   * Handles the get user info action.
   *
   * @Route("/{id}")
   * @Method({"GET"})
   * @throws NotFoundHttpException
   */
  public function showAction(Request $request, $id) {
    return new JsonResponse($this->getUserRepository()->getUserById($id)->toArray());
  }

  /**
   * Handles the edit user action.
   *
   * @Route("/{id}")
   * @Method({"PUT"})
   * @throws InvalidArgumentException
   * @throws NotFoundHttpException
   */
  public function editAction(Request $request, $id) {
    $user = $this->getUserRepository()->getUserById($id);
    $data = $this->getUserDataFromRequest($request, false);

    $this->getUserRepository()->editUser($user, $data);

    $response = new Response('Updated User with id ' . $user->getUserId() . '.', 200);
    $response->headers->set('Location', '/user/' . $user->getUserId());
    return $response;
  }

  /**
   * Handles the delete user action.
   *
   * @Route("/{id}")
   * @Method({"DELETE"})
   * @throws NotFoundHttpException
   */
  public function deleteAction(Request $request, $id) {
    $user = $this->getUserRepository()->getUserById($id);
    $this->getUserRepository()->deleteUser($user);
    return new JsonResponse([]);
  }

  /**
    * Returns the UserRepository instance for this class.
    *
    * @return UserRepository
    */
  private function getUserRepository(): UserRepository {
    return $this->getDoctrine()->getRepository('AppBundle:User');
  }

  /**
   * Returns an array of user data from the given request object.
   *
   * @param Request $request;
   * @param bool $requireEmail (Default true)
   *
   * @return array
   * @throws InvalidArgumentException
   */
  private function getUserDataFromRequest(Request $request, $requireEmail = true): array {
    $data = [];
    foreach(['email', 'address', 'zipCode'] as $fieldName) {
      $data[$fieldName] = $request->get($fieldName, null);
      if (is_string($data[$fieldName])) {
        $data[$fieldName] = trim($data[$fieldName]);
      }
    }

    $data['isActive'] = !!($request->get('isActive', false) === "true" || $request->get('isActive', false) === true);

    if (!$data['email']) {
      if ($requireEmail) {
        throw new InvalidArgumentException('email is required.');
      } else {
        return $data;
      }
    }

    $this->getUserRepository()->checkEmail($data['email']);

    return $data;
  }
}
