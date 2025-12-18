<?php

namespace App\Tests;

use App\Entity\User;
use App\Factory\CommentFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Test\ResetDatabase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiPlatformWithJWTTaskTest extends WebTestCase
{
    use ResetDatabase; 

    private $client = null;

    private function getClient1()
    {
        if (!$this->client) {
            $this->client = static::createClient();
        }
        return $this->client;
    }

    public function testComments(): void 
    {
        $admin = $this->createAdmin();

        list ($user1, $user2, $task1, $task2) = $this->getData();

         CommentFactory::createMany(
            3,
            [
                'author' => $admin,
                'task'=> $task1
            ]
        );

        $client = static::createClient();
        

        $client->request('GET', '/api/comments');
        $this->assertResponseStatusCodeSame(401);

        $token1 = $this->loginUser($user1, 'user1123');
        $token2 = $this->loginUser($user2, 'user1123');
        $adminToken = $this->loginUser($admin, 'admin');

         $client->request(
            'GET',
            '/api/tasks/' . 1234 . '/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ]
        );

        $this->assertResponseStatusCodeSame(404);

        $client->request(
            'GET',
            '/api/tasks/' . $task1->getId() . '/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        $client->request(
            'GET',
            '/api/tasks/' . $task1->getId() . '/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );

        $this->assertResponseIsSuccessful();

        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);
       
        $this->assertSame(3, $data['totalItems']);

        $client->request(
            'GET',
            '/api/tasks/' . $task1->getId() . '/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ]
        );

        $this->assertResponseIsSuccessful();

        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);

        $client->request(
            'GET',
            '/api/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );

        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertSame(count($user1->getComments()), $data['totalItems']);

        $client->request(
            'GET',
            '/api/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertSame(
            count($user1->getComments())
            + count($user2->getComments())
            + count($admin->getComments()), 
            $data['totalItems']
        );
    
        $client->request(
            method: 'POST',
            uri: '/api/tasks/' . $task1->getId(). '/comments',
        
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ],
            content: json_encode([
                    'message' => 'w2'
            ])
        );

        $this->assertResponseStatusCodeSame(422);

        $user1ComentCount = count($user1->getComments());


        $client->request(
            method: 'POST',
            uri: '/api/tasks/' . $task1->getId(). '/comments',
        
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2
            ],
            content: json_encode([
                    'message' => '123'
            ])
        );
        $this->assertResponseStatusCodeSame(403);

        $client->request(
            method: 'POST',
            uri: '/api/tasks/' . $task1->getId(). '/comments',
        
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ],
            content: json_encode([
                    'message' => '123'
            ])
        );
        $this->assertResponseIsSuccessful();

        $client->request(
            method: 'POST',
            uri: '/api/tasks/' . $task1->getId(). '/comments',
        
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ],
            content: json_encode([
                    'message' => '123'
            ])
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $newCommentId = $data['id'];

        $client->request(
            'GET',
            '/api/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertSame($user1ComentCount + 1, $data['totalItems']);

        $client->request(
            'PATCH',
            '/api/comments/' . $newCommentId,
            content: json_encode([
                'message' => 333,
            ])
        );

        $this->assertResponseStatusCodeSame(401);

        $client->request(
            'PATCH',
            '/api/comments/' . $newCommentId,
            content: json_encode([
                'message' => 'qqqwerrrq',
            ]),
            server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ]

        );

        $this->assertResponseStatusCodeSame(200);

        $client->request(
            'PATCH',
            '/api/comments/' . $newCommentId,
            content: json_encode([
                'message' => '12',
            ]),
            server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2
            ]

        );

        $this->assertResponseStatusCodeSame(403);

         $client->request(
            'DELETE',
            '/api/comments/' . $newCommentId
        );
        $this->assertResponseStatusCodeSame(401);

        $client->request(
            'DELETE',
            '/api/comments/' . $newCommentId,
             server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        $client->request(
            'DELETE',
            '/api/comments/' . $newCommentId,
             server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ]
        );
        $this->assertResponseStatusCodeSame(204);
        
        $commentsCount = count($task2->getComments());
        $client->request(
            'DELETE',
            '/api/comments/' . $task2->getComments()->first()->getId(),
             server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2
            ]
        );
        $this->assertResponseStatusCodeSame(204);

        

        $client->request(
            'GET',
            '/api/tasks/' . $task2->getId() . '/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2
            ]
        );

        $this->assertResponseIsSuccessful();
       

        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertSame($commentsCount - 1, $data['totalItems']);

        $client->request(
            'GET',
            '/api/tasks/' . $task2->getId() . '/comments',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ]
        );

        $this->assertResponseIsSuccessful();
       

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($commentsCount, $data['totalItems']);
    }

    private function createAdmin(): User
    {
        return UserFactory::createOne(
            attributes:[
                'email' => 'admin@taskflow.com',
                'plainPassword' => 'admin',
                'roles' => ['ROLE_ADMIN'],
                'name' => 'admin',
            ]
        );
    }

    private function loginUser(User $user, string $password): string
    {
        $client = static::getClient();
        $client->jsonRequest(
            'POST',
            '/api/login_check',
            [
              'username' => $user->getEmail(),
              'password' => $password,
            ]
        );

        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);

        return $data['token'];
    }
    
    public function testSomething(): void
    {
        $admin = $this->createAdmin();

        list ($user1, $user2, $task1, $task2) = $this->getData();

        $client = static::createClient();

        $client->request('GET', '/api/tasks');
   
        $this->assertResponseStatusCodeSame(401);

        $client->request('GET', '/api/tasks/'. $task2->getId());
        $this->assertResponseStatusCodeSame(401);

        $token1 = $this->loginUser($user1, 'user1123');

        $client->request(
            'GET',
            '/api/tasks',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(1, $data['totalItems']);

        $adminToken = $this->loginUser($admin, 'admin');

        $client->request(
            'GET',
            '/api/tasks',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(2, $data['totalItems']);


        $client->request(
            'PATCH',
            '/api/tasks/' . $task2->getId(),
            content: json_encode([
                'id' => 333,
                'title' => 'task 1 updated',
                'status' => 'done',
            ])
        );

        $this->assertResponseStatusCodeSame(401);


        $client->request(
            'PATCH',
            '/api/tasks/' . $task2->getId(),

            content: json_encode( [
                'id' => 333,
                'title' => 'task 2 updated',
                'status' => 'done',
            ]),
            server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );
   //     dd($client->getResponse()->getStatusCode());
   //     dd($client->getResponse()->getContent());
        $this->assertResponseStatusCodeSame(403);

        $client->request(
            'PATCH',
            '/api/tasks/' . $task1->getId(),

           content: json_encode([
                'title' => 'task 1 updated',
                'status' => 'done',
            ]),
            server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]

        );

       // dd($client->getRequest()->__toString());
       // dd($client->getResponse()->getContent());

        $this->assertResponseStatusCodeSame(200);

        $client->request(
            'GET',
            '/api/tasks/' . $task1->getId(),
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('task 1 updated', $data['title']);
        $this->assertSame('done', $data['status']);

        $client->request(
            method: 'POST',
            uri: '/api/tasks',
            server: ['CONTENT_TYPE' => 'application/ld+json'],
            content: json_encode([
                'title' => 'test@example.com',
                'status' => 'todo'
            ])
        );

        $this->assertResponseStatusCodeSame(401);

        $client->request(
            method: 'POST',
            uri: '/api/tasks',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ],
            content: json_encode([
                'title' => 'test@example.com',
                'status' => 'todo'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $content = json_decode($client->getResponse()->getContent());

        $id = $content?->id;

        $this->assertIsInt($id);

        $task = TaskFactory::repository()->find($id);

        $this->assertNotNull($task);

        $client->request(
            'DELETE',
            '/api/tasks/' . $task->getId()
        );
        $this->assertResponseStatusCodeSame(401);

        $client->request(
            'DELETE',
            '/api/tasks/' . $task->getId(),
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ],
        );

        $this->assertResponseStatusCodeSame(204);

        $task = TaskFactory::repository()->find($id);
        $this->assertNull($task);
        $client->request(
            'DELETE',
            '/api/tasks/' . $task2->getId(),
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ],
        );
        $this->assertResponseStatusCodeSame(403);

        $client->request(
            'DELETE',
            '/api/tasks/' . $task2->getId(),
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ],
        );
        $this->assertResponseStatusCodeSame(204);

        $client->request(
            'PATCH',
            '/api/tasks/' . $task1->getId(),

           content: json_encode( [
                'id' => 333,
                'title' => 'task 1 updated by admin',
                'status' => 'done',
            ]),
            server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken
            ]

        );

        $this->assertResponseStatusCodeSame(200);

        $client->request(
            'GET',
            '/api/tasks/' . $task1->getId(),
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('task 1 updated by admin', $data['title']);
        $this->assertSame('done', $data['status']);

    }

    private function getData(): array
    {
        $user1 = UserFactory::createOne(
            attributes:[
                'email' => 'user1@taskflow.com',
                'plainPassword' => 'user1123',
                'roles' => [],
                'name' => 'user1',
            ]
        );

        $task1 = TaskFactory::createOne(
            ['owner' => $user1, 'status' => 'todo']
        );

        $user2 = UserFactory::createOne(
            [
                'email' => 'user2@taskflow.com',
                'plainPassword' => 'user1123',
                'name' => 'user2',
            ]
        );

        $task2 = TaskFactory::createOne(
            ['owner' => $user2]
        );

        CommentFactory::createMany(
            3,
            [
                'author' => $user1,
                'task'=> $task1
            ]
        );

        CommentFactory::createMany(
            2,
            [
                'author' => $user2,
                'task'=> $task2
            ]
        );

            

        return [$user1, $user2, $task1, $task2];
    }
}
