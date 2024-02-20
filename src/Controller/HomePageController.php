<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderLines;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\OrderLinesRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{

    private CategoryRepository $categoryRepository;

    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;

    private OrderLinesRepository $orderLinesRepository;

    public function __construct(CategoryRepository $categoryRepository, OrderRepository $orderRepository, OrderLinesRepository $orderLinesRepository, ProductRepository $productRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->orderLinesRepository = $orderLinesRepository;
    }


    #[Route('/', name: 'app_home_page')]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findAll();

        /** @var User $user */
        $user = $this->getUser();
        $basket = null;
        $options['categories'] = $categories;

        if ($user != null) {
            $orders = $user->getOrders();
            foreach ($orders as $order) {
                if (!$order->isIsValid()) {
                    $basket = $order;
                }
            }

            if ($basket != null) {
                $quantityTotal = 0;
                foreach ($basket->getOrderLines() as $item) {
                    $quantityTotal += $item->getQuantity();
                }
                $options['numberOfProduct'] = $quantityTotal;
            }
        }

        return $this->render('home_page/index.html.twig', $options);
    }

    #[Route('/catégories/{id}', name: 'app_categories_page')]
    public function showCategory(Category $category): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $basket = null;
        $options['products'] = [];

        foreach ($category->getProducts() as $product) {
            if ($product->isVisible()) {
                $options['products'][] = $product;
            }
        }

        if ($user != null) {
            $orders = $user->getOrders();
            foreach ($orders as $order) {
                if (!$order->isIsValid()) {
                    $basket = $order;
                }
            }

            if ($basket != null) {
                $quantityTotal = 0;
                foreach ($basket->getOrderLines() as $item) {
                    $quantityTotal += $item->getQuantity();
                }
                $options['numberOfProduct'] = $quantityTotal;
            }
        }

        return $this->render('products/index.html.twig', $options);
    }

    #[Route('/products/{id}', name: 'app_product_page')]
    public function showProduct(Product $product): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $basket = null;
        $options['product'] = $product;

        if ($user != null) {
            $orders = $user->getOrders();
            foreach ($orders as $order) {
                if (!$order->isIsValid()) {
                    $basket = $order;
                }
            }

            if ($basket != null) {
                $quantityTotal = 0;
                foreach ($basket->getOrderLines() as $item) {
                    $quantityTotal += $item->getQuantity();
                }
                $options['numberOfProduct'] = $quantityTotal;
            }
        }

        return $this->render('products/view_product.html.twig', $options);
    }

    #[Route('/basket/{id}', name: 'app_add_basket_page', methods: ["POST"])]
    public function addBasket(Product $product, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $user->getOrders();
        $newOrder = null;
        $quantity = $request->request->get("quantity");
        $orderLineExist = false;

        foreach ($orders as $order) {
            if (!$order->isIsValid()) {
                $newOrder = $order;
                break;
            }
        }

        if ($newOrder == null) {
            $newOrder = new Order();
            $newOrder->setUser($this->getUser());
            $newOrder->setIsValid(false);
            $newOrder->setAmount($product->getPrice() * $quantity);
        } else {
            $newOrder->setAmount($newOrder->getAmount() + ($product->getPrice() * $quantity));
            foreach ($newOrder->getOrderLines() as $orderLine) {
                if ($product->getId() == $orderLine->getProduct()->getId()) {
                    $orderLine->setQuantity($orderLine->getQuantity() + $quantity);
                    $orderLineExist = true;
                    break;
                }
            }
        }

        if (!$orderLineExist) {
            $orderLine = new OrderLines();
            $orderLine->setProduct($product);
            $orderLine->setQuantity($quantity);
            $newOrder->addOrderLine($orderLine);
        }

        $newOrder->setCreatedAt(new \DateTimeImmutable("now"));
        $this->orderRepository->save($newOrder, true);

        $this->addFlash("success", "le produit a ete ajoute au panier");
        return $this->redirectToRoute("app_categories_page", ["id" => $product->getCategory()->getId()]);
    }

    #[Route('/basket/{id}/delete', name: 'app_reduce_product_quantity')]
    public function reduceProductQuantity(Product $product): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $user->getOrders();
        $basket = null;

        foreach ($orders as $order) {
            if (!$order->isIsValid()) {
                $basket = $order;
                break;
            }
        }

        if ($basket != null) {
            $orderLines = $basket->getOrderLines();
            foreach ($orderLines as $orderLine) {
                if ($orderLine->getProduct()->getId() == $product->getId()) {
                    $basket->setAmount($basket->getAmount() - $product->getPrice());
                    if ($orderLine->getQuantity() <= 1) {
                        $this->orderLinesRepository->remove($orderLine, true);
                        $this->orderRepository->save($basket, true);
                        break;
                    }
                    $orderLine->setQuantity($orderLine->getQuantity() - 1);
                    $this->orderLinesRepository->save($orderLine, true);
                    $this->orderRepository->save($basket, true);
                    break;
                }
            }
        }

        $this->addFlash("success", "le produit a éte supprimer du panier");
        return $this->redirectToRoute("app_basket_page");
    }

    #[Route('/basket/validate', name: 'app_validate_basket')]
    public function validateBasket(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $user->getOrders();
        $basket = null;

        foreach ($orders as $order) {
            if (!$order->isIsValid()) {
                $basket = $order;
                break;
            }
        }

        if ($basket != null) {
            foreach ($basket->getOrderLines() as $orderLine) {
                $product = $orderLine->getProduct();
                $product->setStock($product->getStock() - $orderLine->getQuantity());
                $this->productRepository->save($product, true);
            }
            $basket->setIsValid(true);
            $this->orderRepository->save($basket, true);
        }

        $this->addFlash("success", "la commande a bien éte validé");
        return $this->redirectToRoute("app_home_page");
    }

    #[Route('/basket', name: 'app_basket_page')]
    public function showBasket(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $basket = null;
        $numberOfProduct = 0;

        $orders = $user->getOrders();
        foreach ($orders as $order) {
            if (!$order->isIsValid()) {
                $basket = $order;
            }
        }

        if ($basket != null) {
            $quantityTotal = 0;
            foreach ($basket->getOrderLines() as $item) {
                $quantityTotal += $item->getQuantity();
            }
            $numberOfProduct = $quantityTotal;
        }

        return $this->render('basket/index.html.twig', [
            'basket' => $basket,
            'numberOfProduct' => $numberOfProduct
        ]);
    }

    #[Route('/order', name: 'app_order_page')]
    public function showOrder(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $ordersValide = [];
        $numberOfProduct = 0;

        $orders = $user->getOrders();
        foreach ($orders as $order) {
            if ($order->isIsValid()) {
                $ordersValide[] = $order;
            }
        }

        return $this->render('order/index.html.twig', [
            'orders' => $ordersValide,
            'numberOfProduct' => $numberOfProduct
        ]);
    }

}
