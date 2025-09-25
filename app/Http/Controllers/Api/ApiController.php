<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="BellGas E-commerce API",
 *     version="1.0.0",
 *     description="Complete e-commerce API with authentication, cart, orders, payments, and more",
 *     @OA\Contact(
 *         email="admin@bellgas.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT Authorization header using the Bearer scheme."
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Products",
 *     description="Product management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Categories",
 *     description="Category management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Cart",
 *     description="Shopping cart endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Orders",
 *     description="Order management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Addresses",
 *     description="Address management endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment and checkout endpoints"
 * )
 */
class ApiController extends Controller
{
    //
}