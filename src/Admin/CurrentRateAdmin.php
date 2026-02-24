<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\CurrentRate;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('sonata.admin', [
    'model_class' => CurrentRate::class,
    'manager_type' => 'orm',
    'label' => 'Текущие курсы'
])]
final class CurrentRateAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        $filter
            ->add('charCode', null, ['label' => 'Код валюты'])
            ->add('value', null, ['label' => 'Курс'])
            ->add('updatedAt', null, ['label' => 'Обновлено']);
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('charCode', null, ['label' => 'Код валюты'])
            ->add('value', null, ['label' => 'Курс'])
            ->add('updatedAt', null, [
                'label' => 'Обновлено',
                'format' => 'd.m.Y H:i:s'
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'label' => 'Действия',
                'actions' => [
                    'show' => [],
                    'edit' => [],
                ],
            ]);
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $form
            ->add('charCode', null, ['label' => 'Код валюты (3 символа)'])
            ->add('value', null, ['label' => 'Курс']);
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show
            ->add('charCode', null, ['label' => 'Код валюты'])
            ->add('value', null, ['label' => 'Курс'])
            ->add('updatedAt', null, [
                'label' => 'Обновлено',
                'format' => 'd.m.Y H:i:s'
            ]);
    }
}

