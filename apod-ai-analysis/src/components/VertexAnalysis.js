import React from 'react';

const VertexAnalysis = ({ analysis }) => {
    if (!analysis || analysis.error) return null;

    return (
        <div className="vertex-analysis">
            <h3 className="text-lg font-semibold mb-2">AI Analysis</h3>
            <div className="bg-white rounded-lg p-4 shadow">
                <div className="prose max-w-none">
                    {analysis.split(/(?=\d\. )/).map((section, index) => (
                        <div key={index} className="mb-3">
                            {section.trim()}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default VertexAnalysis;