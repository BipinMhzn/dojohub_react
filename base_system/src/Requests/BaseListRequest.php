<?php


namespace BaseSystem\Requests;


class BaseListRequest extends BaseRequest
{

    public function messages(): array
    {
        return [
            'required_if' => "The :attribute cannot be empty.",
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'page' => ['required_if:page,null', 'integer', 'min:1', 'max:99999999'],
            'sort_by' => ['required_if:sort_by,null', 'string', 'max:255'],
            'sort_order' => ['required_if:sort_order,null', 'string', 'in:asc,desc'],
            'per_page' => ['required_if:per_page,null', 'integer', 'min:3', 'max:500'],
            'keyword' => ['nullable', 'string'],
        ];
    }
}
