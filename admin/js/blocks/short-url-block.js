/**
 * Short URL Block
 * 
 * Registers a Gutenberg block for displaying short URLs
 */
(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
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
            const blockProps = useBlockProps();
            
            const [urls, setUrls] = useState([]);
            const [loading, setLoading] = useState(false);
            const [shortUrl, setShortUrl] = useState('');
            const [error, setError] = useState('');
            
            // Load URLs from the API
            useEffect(() => {
                setLoading(true);
                
                apiFetch({ path: '/wp/v2/short-url/urls' })
                    .then(data => {
                        setUrls(data);
                        setLoading(false);
                    })
                    .catch(err => {
                        setError(__('Error loading URLs', 'short-url'));
                        setLoading(false);
                    });
            }, []);
            
            // Load selected URL data
            useEffect(() => {
                if (urlId > 0) {
                    setLoading(true);
                    
                    apiFetch({ path: `/wp/v2/short-url/urls/${urlId}` })
                        .then(data => {
                            setShortUrl(data.short_url);
                            setLoading(false);
                        })
                        .catch(err => {
                            setError(__('Error loading URL data', 'short-url'));
                            setLoading(false);
                        });
                }
            }, [urlId]);
            
            // Create URL options for select
            const urlOptions = [
                { value: 0, label: __('Select a URL', 'short-url') },
                ...urls.map(url => ({
                    value: url.id,
                    label: url.slug
                }))
            ];
            
            // Handle URL selection
            const onSelectUrl = (value) => {
                setAttributes({ urlId: parseInt(value, 10) });
            };
            
            return (
                <div {...blockProps}>
                    <InspectorControls>
                        <PanelBody title={__('URL Settings', 'short-url')}>
                            <SelectControl
                                label={__('Select URL', 'short-url')}
                                value={urlId}
                                options={urlOptions}
                                onChange={onSelectUrl}
                            />
                            <ToggleControl
                                label={__('Show Copy Button', 'short-url')}
                                checked={showCopyButton}
                                onChange={(value) => setAttributes({ showCopyButton: value })}
                            />
                            {showCopyButton && (
                                <SelectControl
                                    label={__('Button Text', 'short-url')}
                                    value={buttonText}
                                    options={[
                                        { value: __('Copy', 'short-url'), label: __('Copy', 'short-url') },
                                        { value: __('Copy URL', 'short-url'), label: __('Copy URL', 'short-url') },
                                        { value: __('Copy Link', 'short-url'), label: __('Copy Link', 'short-url') }
                                    ]}
                                    onChange={(value) => setAttributes({ buttonText: value })}
                                />
                            )}
                        </PanelBody>
                    </InspectorControls>
                    
                    {loading && (
                        <Placeholder
                            icon="admin-links"
                            label={__('Loading Short URL', 'short-url')}
                            className="short-url-block-placeholder"
                        >
                            <Spinner />
                        </Placeholder>
                    )}
                    
                    {!loading && error && (
                        <Placeholder
                            icon="warning"
                            label={__('Error', 'short-url')}
                            className="short-url-block-placeholder"
                        >
                            {error}
                        </Placeholder>
                    )}
                    
                    {!loading && !error && urlId === 0 && (
                        <Placeholder
                            icon="admin-links"
                            label={__('Short URL', 'short-url')}
                            className="short-url-block-placeholder"
                        >
                            <p>{__('Please select a short URL from the block settings.', 'short-url')}</p>
                        </Placeholder>
                    )}
                    
                    {!loading && !error && urlId > 0 && shortUrl && (
                        <div className="wp-block-short-url">
                            <span className="wp-block-short-url-icon dashicons dashicons-admin-links"></span>
                            <a href={shortUrl} className="wp-block-short-url-link" target="_blank" rel="noopener noreferrer">
                                {shortUrl}
                            </a>
                            {showCopyButton && (
                                <button className="wp-block-short-url-copy" disabled>
                                    {buttonText}
                                </button>
                            )}
                        </div>
                    )}
                </div>
            );
        },
        
        save: function() {
            // Dynamic block, save nothing
            return null;
        }
    });
})(window.wp); 