# Info

Welcome to the Ozan Agreement API.

<h2>Request:</h2>
It authenticates your API requests using your account’s <b>API key</b>. If you do not include your key when making an API request then returns an error.

<h2>Response:</h2>
Response Details
<table>
    <thead>
        <tr>
            <th>Parameter</th>
            <th>Type</th>
            <th>Description</th>
            <th>Example</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>status</code></td>
            <td>char(24)</td>
            <td>Api request status</td>
            <td>APPROVED</td>
        </tr>
        <tr>
            <td><code>code</code></td>
            <td>char(4)</td>
            <td>Status code</td>
            <td>00</td>
        </tr>
        <tr>
            <td><code>message</code></td>
            <td>char(256)</td>
            <td>Status message</td>
            <td>Success</td>
        </tr>
        <tr>
            <td><code>data</code></td>
            <td>array, object</td>
            <td>Response data</td>
            <td>{provisionNo: 1234, date:1607275358}</td>
        </tr>
    </tbody>
</table>

@if($showPostmanCollectionButton)
[Get Postman Collection]({{url($outputPath.'/collection.json')}})
@endif
