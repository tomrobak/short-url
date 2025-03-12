/**
 * Short URL Block
 * 
 * Registers a Gutenberg block for displaying short URLs
 */
(function(wp) {
    'use strict';
    
    if (!wp) {
        console.error('WordPress object not found. Gutenberg blocks not initialized.');
        return;
    }
    
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
    const { PanelBody, SelectControl, ToggleControl, Button, Placeholder, Spinner } = wp.components;
    const { useState, useEffect } = wp.element;
    const { apiFetch } = wp;
    
    // Register the block
    registerBlockType('short-url/url-block', {
        title: __('Short URL', 'short-url'),
        description: __('Add a short URL to your content', 'short-url'),
        icon: 'admin-links',
        category: 'widgets',
        attributes: {
            urlId: {
                type: 'number',
                default: 0
            },
            showCopyButton: {
                type: 'boolean',
                default: true
            },
            buttonText: {
                type: 'string',
                default: __('Copy', 'short-url')
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { urlId, showCopyButton, buttonText } = attributes;
            const blockProps = useBlockProps ? useBlockProps() : {};
            
            const [urls, setUrls] = useState([]);
            const [loading, setLoading] = useState(false);
            const [shortUrl, setShortUrl] = useState('');
            const [error, setError] = useState('');
            
            // Load URLs from the API
            useEffect(function() {
                setLoading(true);
                
                apiFetch({ path: '/wp/v2/short-url/urls' })
                    .then(function(data) {
                        setUrls(data);
                        setLoading(false);
                    })
                    .catch(function(err) {
                        setError(__('Error loading URLs', 'short-url'));
                        setLoading(false);
                    });
            }, []);
            
            // Load selected URL data
            useEffect(function() {
                if (urlId > 0) {
                    setLoading(true);
                    
                    apiFetch({ path: '/wp/v2/short-url/urls/' + urlId })
                        .then(function(data) {
                            setShortUrl(data.short_url);
                            setLoading(false);
                        })
                        .catch(function(err) {
                            setError(__('Error loading URL data', 'short-url'));
                            setLoading(false);
                        });
                }
            }, [urlId]);
            
            // Create URL options for select
            const urlOptions = [
                { value: 0, label: __('Select a URL', 'short-url') }
            ].concat(urls.map(function(url) {
                return {
                    value: url.id,
                    label: url.slug
                };
            }));
            
            // Handle URL selection
            const onSelectUrl = function(value) {
                setAttributes({ urlId: parseInt(value, 10) });
            };
            
            // Use createElement instead of JSX
            const { createElement } = wp.element;
            
            const inspectorControls = createElement(
                InspectorControls,
                null,
                createElement(
                    PanelBody,
                    { title: __('URL Settings', 'short-url') },
                    createElement(SelectControl, {
                        label: __('Select URL', 'short-url'),
                        value: urlId,
                        options: urlOptions,
                        onChange: onSelectUrl
                    }),
                    createElement(ToggleControl, {
                        label: __('Show Copy Button', 'short-url'),
                        checked: showCopyButton,
                        onChange: function(value) {
                            setAttributes({ showCopyButton: value });
                        }
                    }),
                    showCopyButton && createElement(SelectControl, {
                        label: __('Button Text', 'short-url'),
                        value: buttonText,
                        options: [
                            { value: __('Copy', 'short-url'), label: __('Copy', 'short-url') },
                            { value: __('Copy URL', 'short-url'), label: __('Copy URL', 'short-url') },
                            { value: __('Copy Link', 'short-url'), label: __('Copy Link', 'short-url') }
                        ],
                        onChange: function(value) {
                            setAttributes({ buttonText: value });
                        }
                    })
                )
            );
            
            let content;
            
            if (loading) {
                content = createElement(
                    Placeholder,
                    {
                        icon: 'admin-links',
                        label: __('Loading Short URL', 'short-url'),
                        className: 'short-url-block-placeholder'
                    },
                    createElement(Spinner)
                );
            } else if (error) {
                content = createElement(
                    Placeholder,
                    {
                        icon: 'warning',
                        label: __('Error', 'short-url'),
                        className: 'short-url-block-placeholder'
                    },
                    error
                );
            } else if (urlId === 0) {
                content = createElement(
                    Placeholder,
                    {
                        icon: 'admin-links',
                        label: __('Short URL', 'short-url'),
                        className: 'short-url-block-placeholder'
                    },
                    createElement(
                        'p',
                        null,
                        __('Please select a short URL from the block settings.', 'short-url')
                    )
                );
            } else if (shortUrl) {
                content = createElement(
                    'div',
                    { className: 'wp-block-short-url' },
                    createElement(
                        'div',
                        { className: 'wp-block-short-url-container' },
                        createElement('span', { 
                            className: 'wp-block-short-url-icon dashicons dashicons-admin-links',
                            style: { 
                                color: '#0073aa',
                                fontSize: '20px',
                                width: '24px',
                                height: '24px',
                                marginRight: '8px'
                            }
                        }),
                        createElement(
                            'a',
                            {
                                href: shortUrl,
                                className: 'wp-block-short-url-link',
                                target: '_blank',
                                rel: 'noopener noreferrer',
                                style: {
                                    fontFamily: 'monospace',
                                    fontSize: '16px',
                                    color: '#0073aa',
                                    textDecoration: 'none',
                                    fontWeight: '500',
                                    marginRight: '10px',
                                    display: 'inline-block',
                                    maxWidth: '100%',
                                    overflow: 'hidden',
                                    textOverflow: 'ellipsis'
                                }
                            },
                            shortUrl
                        )
                    ),
                    showCopyButton && createElement(
                        'div',
                        { className: 'wp-block-short-url-actions', style: { marginTop: '8px' } },
                        createElement(
                            'button',
                            {
                                className: 'wp-block-short-url-copy',
                                style: {
                                    backgroundColor: '#0073aa',
                                    color: '#fff',
                                    border: 'none',
                                    borderRadius: '4px',
                                    padding: '6px 12px',
                                    cursor: 'pointer',
                                    fontSize: '14px',
                                    display: 'flex',
                                    alignItems: 'center'
                                },
                                disabled: true
                            },
                            createElement('span', {
                                className: 'dashicons dashicons-clipboard',
                                style: {
                                    fontSize: '16px',
                                    width: '16px',
                                    height: '16px',
                                    marginRight: '4px'
                                }
                            }),
                            buttonText
                        )
                    )
                );
            }
            
            return createElement(
                'div',
                blockProps,
                inspectorControls,
                content
            );
        },
        
        save: function() {
            // Dynamic block, save nothing
            return null;
        }
    });
})(window.wp); 