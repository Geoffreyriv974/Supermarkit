<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $routeBuilder = $this->container->get(AdminUrlGenerator::class);

        if ($this->isGranted('ROLE_CASHIER')) {
            $url = $routeBuilder->setController(ProductCrudController::class)->generateUrl();
        }
        if ($this->isGranted('ROLE_ACCOUNTANT')) {
            $url = $this->generateUrl("app_finance_page");
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            $url = $routeBuilder->setController(UserCrudController::class)->generateUrl();
        }

        return $this->redirect($url);
    }

    #[Route('/admin/finance', name: 'app_finance_page')]
    public function finance(): Response
    {
        return $this->render("finance/index.html.twig");
    }

    public function configureMenuItems(): iterable
    {

        if ($this->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkToCrud('User crud', 'fa fa-user', User::class);
            yield MenuItem::linkToCrud('Order crud', 'fa fa-cart-shopping', Order::class);
        }
        if ($this->isGranted('ROLE_CASHIER')) {
            yield MenuItem::linkToCrud('Product crud', 'fa fa-list', Product::class);
            yield MenuItem::linkToCrud('Category crud', 'fa fa-tags', Category::class);
        }
        if ($this->isGranted('ROLE_ACCOUNTANT')) {
            yield MenuItem::linkToUrl('Finance', "fa fa-chart-simple", $this->generateUrl("app_finance_page"));
        }

        yield MenuItem::linkToRoute('Return SuperMarkit', 'fas fa-home', 'app_home_page');
    }
}
