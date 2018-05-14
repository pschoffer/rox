<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class LogFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $formBuilder
     * @param array                $options
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function buildForm(FormBuilderInterface $formBuilder, array $options)
    {
        $formBuilder
            ->add('types', ChoiceType::class, [
                'choices' => $options['data']['logTypes'],
                'required' => false,
                'multiple' => true,
                'attr' => [
                    'class' => 'select2',
                ],
            ])
            ->add('username', TextType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'member-autocomplete',
                ],
            ])
            ->add('ipaddress', TextType::class, [
                'required' => false,
                'attr' => [
                    'pattern' => '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}',
                ],
                'label' => 'IP address',
            ])
            ->add('Update', SubmitType::class);
    }
}
