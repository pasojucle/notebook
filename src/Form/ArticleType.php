<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Chapter;
use App\Entity\Section;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $btnAdd = 'btn btn-add form-item-inline fas ';

        $builder
            ->add('sectionTitle', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nouvelle rubrique',
                ],
                'label' => 'Rubrique',
                'row_attr' =>  [
                    'class' => $options['add_section']  ? 'form-item-inline w-90' : 'hidden',
                ],
                'required' => $options['add_section']  ? true : false,
            ])
            ->add('section', EntityType::class, [
                'class' => Section::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('s')
                        ->orderBy('s.title', 'ASC');
                },
                'choice_label' => 'title',
                'placeholder' => 'Choisir une rubrique',
                'label' => 'Rubrique',
                'row_attr' =>  [
                    'class' => $options['add_section']  ? 'hidden' :'form-item-inline w-90',
                ],
                'required' => $options['add_section'] ? false : true,
            ])
            ->add('title', TextType::class, [
                'attr' => [
                    'placeholder' => 'Titre'
                ],
                'label' => 'Titre',
                ])
            ->add('content', CKEditorType::class, [])
            ->add('addSection', ButtonType::class, [
                'attr' =>[
                    'class' => (!$options['add_section']) ? $btnAdd.'fa-plus-square btn-primary' : $btnAdd.'fa-minus-square btn-secondary',
                    'data-value' => (int)$options['add_section'],
                ],
                'label' => ' ',
            ])
            ->add('addChapter', ButtonType::class, [
                'attr' =>[
                    'class' => (!$options['add_chapter']) ? $btnAdd.'fa-plus-square btn-primary' : $btnAdd.'fa-minus-square btn-secondary',
                    'data-value' => (int)$options['add_chapter'],
                ],
                'label' => ' ',
                'disabled' => ($options['add_section']) ? true : false,
            ])
            ;

        $formModifier = function (FormInterface $form, Section $section = null, bool $isPrivate = false) use ($options) {
            $chapters = null == $section ? [] : $section->getChapters();
            $form->add('chapter', EntityType::class, [
                'class' => Chapter::class,
                'placeholder' => 'Choisir un chapitre',
                'choices' => $chapters,
                'choice_label' => 'title',
                'label' => 'Chapitre',
                'row_attr' =>  [
                    'class' => ($options['add_section'] || $options['add_chapter']) ? 'hidden' : 'form-item-inline w-90',
                ],
                'required' => ($options['add_section'] || $options['add_chapter']) ? false : true,
            ])
            ->add('chapterTitle', TextType::class, [
                'attr' => [
                    'placeholder' => 'nouveau Chapitre'
                ],
                'label' => 'Chapitre',
                'row_attr' =>  [
                    'class' => ($options['add_section'] || $options['add_chapter']) ? 'form-item-inline w-90' : 'hidden'
                ],
                'required' => ($options['add_section'] || $options['add_chapter']) ? true : false,
            ])
            ->add('isPrivate', CheckboxType::class, [
                'block_prefix' => 'switch',
                'required' => false,
                'label' => 'Article privé',
                'data' => $isPrivate,
            ])
            ;
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                $section = (null !== $data) ? $data->getSection() : null;
                $user = (null !== $data) ? $data->getUser() : null;
                $isPrivate = (null === $data || null === $user) ? false : true;
                $formModifier($event->getForm(), $section, $isPrivate);
            }
        );

        $builder->get('section')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $section = $event->getForm()->getData();
                $formModifier($event->getForm()->getParent(), $section, false);
            }
        );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
            'add_section' => false,
            'add_chapter' => false,
        ]);
    }
}
