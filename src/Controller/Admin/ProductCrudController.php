<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use phpDocumentor\Reflection\DocBlock\Tags\Property;
use Symfony\Bundle\MakerBundle\Str;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_CASHIER')
            ->setPermission(Action::DETAIL, 'ROLE_CASHIER')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_CASHIER');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('name'),
            ImageField::new('picture')
                ->setUploadDir("/public/products")
                ->setBasePath("/products")
                ->setRequired(false),
            TextareaField::new('description'),
            DateTimeField::new('created_at')
                ->hideOnForm(),
            DateTimeField::new('updated_at')
                ->hideOnForm(),
            BooleanField::new('visible'),
            IntegerField::new('stock'),
            AssociationField::new('category'),
            MoneyField::new('price')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),
        ];
    }

    public function createEntity(string $entityFqcn): Product
    {
        $product = new Product();
        $product->setCreatedAt(new \DateTimeImmutable("now"));

        return $product;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->setUpdatedAt(new \DateTimeImmutable("now"));
        $entityManager->persist($entityInstance);
        $entityManager->flush();
    }

}
