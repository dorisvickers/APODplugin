import React, { useState } from 'react';

const KeywordAnalysis = ({ keywords, technicalTerms, measurements, category }) => {
    const [expanded, setExpanded] = useState(false);

    return (
        <div className="keyword-analysis mt-4">
            <h3 className="text-lg font-semibold mb-2">Content Analysis</h3>
            <div className="bg-gray-50 rounded-lg p-4">
                <div className="mb-3">
                    <span className="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm">
                        {category.name}
                    </span>
                </div>

                <div className={`${expanded ? '' : 'max-h-48 overflow-hidden'}`}>
                    {keywords.length > 0 && (
                        <div className="mb-3">
                            <h4 className="text-sm font-medium mb-1">Key Topics:</h4>
                            <div className="flex flex-wrap gap-1">
                                {keywords.map((keyword, index) => (
                                    <span key={index} className="bg-gray-200 px-2 py-1 rounded-md text-sm">
                                        {keyword}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    {technicalTerms.length > 0 && (
                        <div className="mb-3">
                            <h4 className="text-sm font-medium mb-1">Technical Terms:</h4>
                            <div className="flex flex-wrap gap-1">
                                {technicalTerms.map((term, index) => (
                                    <span key={index} className="bg-purple-100 text-purple-800 px-2 py-1 rounded-md text-sm">
                                        {term}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}

                    {measurements.length > 0 && (
                        <div>
                            <h4 className="text-sm font-medium mb-1">Measurements:</h4>
                            <div className="flex flex-wrap gap-1">
                                {measurements.map((measurement, index) => (
                                    <span key={index} className="bg-green-100 text-green-800 px-2 py-1 rounded-md text-sm">
                                        {measurement}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {(keywords.length + technicalTerms.length + measurements.length > 10) && (
                    <button
                        onClick={() => setExpanded(!expanded)}
                        className="text-blue-600 hover:text-blue-800 text-sm mt-2"
                    >
                        {expanded ? 'Show Less' : 'Show More'}
                    </button>
                )}
            </div>
        </div>
    );
};

export default KeywordAnalysis;