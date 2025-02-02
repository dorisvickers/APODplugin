import React from 'react';

const SimilarImages = ({ similar }) => {
    if (!similar || similar.length === 0) return null;

    return (
        <div className="similar-images mt-4">
            <h3 className="text-lg font-semibold mb-2">Similar Images</h3>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {similar.map(({ image, score }) => (
                    <div key={image.date} className="bg-white rounded-lg shadow overflow-hidden">
                        <a 
                            href={`https://apod.nasa.gov/apod/ap${image.date.replace(/-/g, '').slice(2)}.html`}
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="block"
                        >
                            <img 
                                src={image.imageUrl} 
                                alt={image.title}
                                className="w-full h-32 object-cover"
                            />
                            <div className="p-3">
                                <h4 className="font-medium text-sm mb-1 truncate">
                                    {image.title}
                                </h4>
                                <div className="flex justify-between items-center">
                                    <span className="text-gray-600 text-xs">
                                        {image.date}
                                    </span>
                                    <span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                        {score}% match
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default SimilarImages;