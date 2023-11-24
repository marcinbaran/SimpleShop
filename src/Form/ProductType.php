<?php

namespace App\Form;

use App\Entity\ProductImage;
use App\Entity\Product;
use App\Entity\ProductCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->images = $options['images'];
        $builder
            ->add('name')
            ->add('description')
            ->add('categories', EntityType::class, [
                'class' => ProductCategory::class,
                'multiple' => true,
                'choice_label' => function ($categories) {
                    return $categories->getName();
                }
            ])
            ->add('price', NumberType::class)
            ->add('images', FileType::class, [
                'multiple' => true,
                'data_class' => null,
                'mapped' => false,
                'required' => false
            ])
            ->add('defaultImage', EntityType::class, [
                'class' => ProductImage::class,
                'multiple' => false,
                'mapped' => false,
                'choices' => $this->images,
                'choice_label' => function ($images) {
                    return $images->getFileName();
                },
                'choice_attr' => function ($images) {
                    return ['data-imagesrc' => '/upload/images/' . $images->getFileName()
                    ];
                }
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'images' => ProductImage::class
        ]);
    }
}