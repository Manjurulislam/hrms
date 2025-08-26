import { CircleIcon, PointIcon } from 'vue-tabler-icons';

export interface menu {
    header?: string;
    title?: string;
    icon?: any;
    to?: string;
    divider?: boolean;
    chip?: string;
    chipColor?: string;
    chipVariant?: string;
    chipIcon?: string;
    children?: menu[];
    disabled?: boolean;
    subCaption?: string;
    class?: string;
    extraclass?: string;
    type?: string;
}

const horizontalItems: menu[] = [
    {
        title: 'Dashboard',
        icon: 'layers-line-duotone',
        to: '#',
        children: [
            {
                title: 'Dashboard 1',
                icon: CircleIcon,
                to: '/dashboards/dashboard1'
            },
            {
                title: 'Dashboard 2',
                icon: CircleIcon,
                to: '/dashboards/dashboard2'
            },
            {
                title: 'Dashboard 3',
                icon: CircleIcon,
                to: '/dashboards/dashboard3'
            }
        ]
    },
    {
        title: 'Front Pages',
        icon: 'home-angle-linear',
        to: '#',
        children: [
            {
                title: 'Homepage',
                icon: CircleIcon,
                to: '/front-page/homepage'
            },
            {
                title: 'About Us',
                icon: CircleIcon,
                to: '/front-page/about-us'
            },
            {
                title: 'Blog',
                icon: CircleIcon,
                to: '/front-page/blog/posts'
            },
            {
                title: 'Blog Details',
                icon: CircleIcon,
                to: '/front-page/blog/early-black-friday-amazon-deals-cheap-tvs-headphones'
            },
            {
                title: 'Contact Us',
                icon: CircleIcon,
                to: '/front-page/contact-us'
            },
            {
                title: 'Portfolio',
                icon: CircleIcon,
                to: '/front-page/portfolio'
            },
            {
                title: 'Pricing',
                icon: CircleIcon,
                to: '/front-page/pricing'
            }
        ]
    },

    {
        title: 'Apps',
        icon: 'widget-line-duotone',
        to: '#',
        children: [
            {
                title: 'ECommerce',
                icon: CircleIcon,
                to: '/ecommerce/',
                children: [
                    {
                        title: 'Shop',
                        to: '/ecommerce/products'
                    },
                    {
                        title: 'Detail',
                        to: '/ecommerce/product/detail/1'
                    },
                    {
                        title: 'List',
                        to: '/ecommerce/productlist'
                    },
                    {
                        title: 'Checkout',
                        to: '/ecommerce/checkout'
                    },
                    {
                        title: 'Add Product',
                        to: '/ecommerce/add-product'
                    },
                    {
                        title: 'Edit Product',
                        to: '/ecommerce/edit-product'
                    }
                ]
            },
            {
                title: 'Blog',
                icon: 'widget-4-line-duotone',
                to: '/',
                children: [
                    {
                        title: 'Blog Posts',
                        to: '/apps/blog/posts'
                    },
                    {
                        title: 'Blog Details',
                        to: '/apps/blog/early-black-friday-amazon-deals-cheap-tvs-headphones'
                    }
                ]
            },
            {
                title: 'User Profile',
                icon: 'shield-user-line-duotone',
                to: '/',
                children: [
                    {
                        title: 'Profile',
                        to: '/apps/user/profile'
                    },
                    {
                        title: 'Followers',
                        to: '/apps/user/profile/followers'
                    },
                    {
                        title: 'Friends',
                        to: '/apps/user/profile/friends'
                    },
                    {
                        title: 'Gallery',
                        to: '/apps/user/profile/gallery'
                    }
                ]
            },
            {
                title: 'Invoice',
                icon: 'bill-check-outline',
                to: '/',
                children: [
                    {
                        title: 'List',
                        to: '/apps/invoice'
                    },
                    {
                        title: 'Details',
                        to: '/apps/invoice/details/102'
                    },
                    {
                        title: 'Create',
                        to: '/apps/invoice/create'
                    },
                    {
                        title: 'Edit',
                        to: '/apps/invoice/edit/102'
                    }
                ]
            },
            {
                title: 'Calendar',
                icon: CircleIcon,
                to: '/apps/calendar'
            },
            {
                title: 'Email',
                icon: CircleIcon,
                to: '/apps/email'
            },
            {
                title: 'Chats',
                icon: CircleIcon,
                to: '/apps/chats'
            },
            {
                title: 'Notes',
                icon: CircleIcon,
                to: '/apps/notes'
            },
            {
                title: 'Kanban',
                icon: CircleIcon,
                to: '/apps/kanban'
            },

            {
                title: 'Contact',
                icon: CircleIcon,
                to: '/apps/contacts'
            },
            {
                title: 'Tickets',
                icon: CircleIcon,
                to: '/apps/tickets'
            },
        ]
    },

    {
        title: 'Pages',
        icon: 'notes-line-duotone',
        to: '#',
        children: [
            {
                title: 'Pricing',
                icon: 'dollar-line-duotone',
                to: '/pages/pricing'
            },
            {
                title: 'Account Setting',
                icon: 'settings-minimalistic-line-duotone',
                to: '/pages/account-settings'
            },
            {
                title: 'FAQ',
                icon: 'question-circle-line-duotone',
                to: '/pages/faq'
            },
            {
                title: 'Gallery Lightbox',
                icon: CircleIcon,
                to: '/pages/gallery-lightbox'
            },
            {
                title: 'Search Results',
                icon: CircleIcon,
                to: '/pages/search-results'
            },
            {
                title: 'Social Contacts',
                icon: CircleIcon,
                to: '/pages/social-media-contacts'
            },
            {
                title: 'Treeview',
                icon: CircleIcon,
                to: '/pages/treeview'
            },
            {
                title: 'Widget',
                icon: 'cardholder-line-duotone',
                to: '/widget-card',
                children: [
                    {
                        title: 'Cards',
                        to: '/widgets/cards'
                    },
                    {
                        title: 'Banners',
                        to: '/widgets/banners'
                    },
                    {
                        title: 'Charts',
                        to: '/widgets/charts'
                    }
                ]
            },
            {
                title: 'Landing Page',
                icon: 'passport-line-duotone',
                to: '/'
            },
            {
                title: 'UI Elements',
                icon: 'waterdrops-line-duotone',
                to: '#',
                children: [
                    {
                        title: 'Alert',
                        to: '/ui-components/alert'
                    },
                    {
                        title: 'Accordion',
                        to: '/ui-components/accordion'
                    },
                    {
                        title: 'Avatar',
                        to: '/ui-components/avatar'
                    },
                    {
                        title: 'Chip',
                        to: '/ui-components/chip'
                    },
                    {
                        title: 'Dialog',
                        to: '/ui-components/dialogs'
                    },
                    {
                        title: 'List',
                        to: '/ui-components/list'
                    },
                    {
                        title: 'Menus',
                        to: '/ui-components/menus'
                    },
                    {
                        title: 'Rating',
                        to: '/ui-components/rating'
                    },
                    {
                        title: 'Tabs',
                        to: '/ui-components/tabs'
                    },
                    {
                        title: 'Tooltip',
                        to: '/ui-components/tooltip'
                    },
                    {
                        title: 'Typography',
                        to: '/ui-components/typography'
                    }
                ]
            },
            {
                title: 'Charts',
                icon: CircleIcon,
                to: '#',
                children: [
                    {
                        title: 'Line',
                        icon: CircleIcon,
                        to: '/charts/line-chart'
                    },
                    {
                        title: 'Gredient',
                        icon: CircleIcon,
                        to: '/charts/gredient-chart'
                    },
                    {
                        title: 'Area',
                        icon: CircleIcon,
                        to: '/charts/area-chart'
                    },
                    {
                        title: 'Candlestick',
                        icon: CircleIcon,
                        to: '/charts/candlestick-chart'
                    },
                    {
                        title: 'Column',
                        icon: CircleIcon,
                        to: '/charts/column-chart'
                    },
                    {
                        title: 'Doughnut & Pie',
                        icon: CircleIcon,
                        to: '/charts/doughnut-pie-chart'
                    },
                    {
                        title: 'Radialbar & Radar',
                        icon: CircleIcon,
                        to: '/charts/radialbar-chart'
                    }
                ]
            },
            {
                title: 'Auth',
                icon: CircleIcon,
                to: '#',
                children: [
                    {
                        title: 'Error',
                        icon: CircleIcon,
                        to: '/auth/404'
                    },
                    {
                        title: 'Maintenance',
                        icon: CircleIcon,
                        to: '/auth/maintenance'
                    },
                    {
                        title: 'Login',
                        icon: CircleIcon,
                        to: '#',
                        children: [
                            {
                                title: 'Side Login',
                                icon: CircleIcon,
                                to: '/auth/login'
                            },
                            {
                                title: 'Boxed Login',
                                icon: CircleIcon,
                                to: '/'
                            }
                        ]
                    },
                    {
                        title: 'Register',
                        icon: CircleIcon,
                        to: '#',
                        children: [
                            {
                                title: 'Side Register',
                                icon: CircleIcon,
                                to: '/auth/register'
                            },
                            {
                                title: 'Boxed Register',
                                icon: CircleIcon,
                                to: '/auth/register2'
                            }
                        ]
                    },
                    {
                        title: 'Forgot Password',
                        icon: CircleIcon,
                        to: '#',
                        children: [
                            {
                                title: 'Side Forgot Password',
                                icon: CircleIcon,
                                to: '/auth/forgot-password'
                            },
                            {
                                title: 'Boxed Forgot Password',
                                icon: CircleIcon,
                                to: '/auth/forgot-password2'
                            }
                        ]
                    },
                    {
                        title: 'Two Steps',
                        icon: CircleIcon,
                        to: '#',
                        children: [
                            {
                                title: 'Side Two Steps',
                                icon: CircleIcon,
                                to: '/auth/two-step'
                            },
                            {
                                title: 'Boxed Two Steps',
                                icon: CircleIcon,
                                to: '/auth/two-step2'
                            }
                        ]
                    }
                ]
            }
        ]
    },

    {
        title: 'Forms',
        icon: 'folder-line-duotone',
        to: '#',
        children: [
            {
                title: 'Form Elements',
                icon: CircleIcon,
                to: '/components/',
                children: [
                    {
                        title: 'Autocomplete',
                        icon: PointIcon,
                        to: '/forms/form-elements/autocomplete'
                    },
                    {
                        title: 'Combobox',
                        icon: PointIcon,
                        to: '/forms/form-elements/combobox'
                    },
                    {
                        title: 'Button',
                        icon: PointIcon,
                        to: '/forms/form-elements/button'
                    },
                    {
                        title: 'Checkbox',
                        icon: PointIcon,
                        to: '/forms/form-elements/checkbox'
                    },
                    {
                        title: 'Custom Inputs',
                        icon: PointIcon,
                        to: '/forms/form-elements/custominputs'
                    },
                    {
                        title: 'File Inputs',
                        icon: PointIcon,
                        to: '/forms/form-elements/fileinputs'
                    },
                    {
                        title: 'Radio',
                        icon: PointIcon,
                        to: '/forms/form-elements/radio'
                    },
                    {
                        title: 'Select',
                        icon: PointIcon,
                        to: '/forms/form-elements/select'
                    },
                    {
                        title: 'Date Time',
                        icon: PointIcon,
                        to: '/forms/form-elements/date-time'
                    },
                    {
                        title: 'Slider',
                        icon: PointIcon,
                        to: '/forms/form-elements/slider'
                    },
                    {
                        title: 'Switch',
                        icon: PointIcon,
                        to: '/forms/form-elements/switch'
                    },
                    {
                        title: 'Time Picker',
                        icon: PointIcon,
                        to: '/forms/form-elements/time-picker'
                    },

                    {
                        title: 'Stepper',
                        icon: PointIcon,
                        to: '/forms/form-elements/stepper'
                    }
                ]
            },
            {
                title: 'Form Layout',
                icon: CircleIcon,
                to: '/forms/form-layouts'
            },
            {
                title: 'Form Horizontal',
                icon: CircleIcon,
                to: '/forms/form-horizontal'
            },
            {
                title: 'Form Vertical',
                icon: CircleIcon,
                to: '/forms/form-vertical'
            },
            {
                title: 'Form Custom',
                icon: CircleIcon,
                to: '/forms/form-custom'
            },
            {
                title: 'Form Validation',
                icon: CircleIcon,
                to: '/forms/form-validation'
            }
        ]
    },

    {
        title: 'Tables',
        icon: 'bedside-table-3-outline',
        to: '#',
        children: [
            {
                title: 'Basic Table',
                icon: CircleIcon,
                to: '/tables/basic'
            },
            {
                title: 'Dark Table',
                icon: CircleIcon,
                to: '/tables/dark'
            },
            {
                title: 'Density Table',
                icon: CircleIcon,
                to: '/tables/density'
            },
            {
                title: 'Fixed Header Table',
                icon: CircleIcon,
                to: '/tables/fixed-header'
            },
            {
                title: 'Height Table',
                icon: CircleIcon,
                to: '/tables/height'
            },
            {
                title: 'Editable Table',
                icon: CircleIcon,
                to: '/tables/editable'
            }
        ]
    },
    {
        title: 'Data Tables',
        icon: 'database-broken',
        to: '#',
        children: [
            {
                title: 'Basic Table',
                icon: CircleIcon,
                to: '/tables/datatables/basic'
            },
            {
                title: 'Header Table',
                icon: CircleIcon,
                to: '/tables/datatables/header'
            },
            {
                title: 'Selection Table',
                icon: CircleIcon,
                to: '/tables/datatables/selection'
            },
            {
                title: 'Sorting Table',
                icon: CircleIcon,
                to: '/tables/datatables/sorting'
            },
            {
                title: 'Pagination Table',
                icon: CircleIcon,
                to: '/tables/datatables/pagination'
            },
            {
                title: 'Filtering Table',
                icon: CircleIcon,
                to: '/tables/datatables/filtering'
            },
            {
                title: 'Grouping Table',
                icon: CircleIcon,
                to: '/tables/datatables/grouping'
            },
            {
                title: 'Table Slots',
                icon: CircleIcon,
                to: '/tables/datatables/slots'
            },
            {
                title: 'CRUD Table',
                icon: CircleIcon,
                to: '/tables/datatables/crudtable'
            }
        ]
    },

    {
        title: 'Icons',
        icon: 'sticker-smile-square-line-duotone',
        to: '#',
        children: [
            {
                title: 'Tabler Icons',
                icon: CircleIcon,
                to: '/icons/tabler'
            },
            {
                title: 'Solar Icons',
                icon: CircleIcon,
                to: '/icons/solar'
            }
        ]
    }
];

export default horizontalItems;
